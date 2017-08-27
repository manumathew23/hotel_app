<?php

class Hotel
{
    private $connection;
    public function __construct()
    {
        $this->connection = new mysqli(SERVERNAME, USERNAME, PASSWORD);
        if ($this->connection->connect_errno) {
            return false;
        }
        $this->connection->select_db(DBNAME);
    }
    public function listAvailableRooms($from_date = null, $to_date = null, $type = "%", $capacity = "%", $lower_price = 0, $higher_price = 100000)
    {
        $available_rooms = [];
        if (!$this->checkArguments(func_get_args())) {
            return false;
        }

        //Get all the rooms that are not at all booked.
        $where = " room.room_no not in (select room_no from reservation) AND  
                room.type like '" . $type . "' AND room.capacity like '" . $capacity .
                "' AND room.price < " . $higher_price . " AND room.price > " . $lower_price;
        $available_rooms1 = $this->getData('room', array('room_no'), $where);
        if ($available_rooms1 === false) {
            return false;
        } else {
            $available_rooms = array_merge($available_rooms, $available_rooms1);
        }

        //Get all the rooms that are available for the required duration.
        $join = " LEFT JOIN room ON reservation.room_no = room.room_no ";
        $where = "(( '$from_date'  < from_date AND  '$to_date' < from_date )  
             OR ( '$from_date'  > to_date AND  '$to_date'  > to_date ) 
             OR reservation.status like 'cancelled') 
             AND room.type like '$type' AND room.capacity like '$capacity' 
             AND room.price < '$higher_price'  AND room.price > '$lower_price'";
        $available_rooms2 = $this->getData('reservation', array('reservation.room_no'), $where, $join);
        if (!$available_rooms2 === false) {
            $available_rooms = array_merge($available_rooms, $available_rooms2);
        }
        if (!empty($available_rooms)) {
            return $available_rooms;
        } else {
            return null;
        }
    }
    public function listAllrooms($type = "%", $capacity = "%", $price = "%")
    {
        $where = " type like '$type' and capacity like '$capacity' and price like '$price'";
        if (empty($this->getData('room', array('room_no'), $where))) {
            return null;
        }

        return $this->getData('room', array('room_no'), $where);
    }
    public function getRoomDetails($room_no = null)
    {
        if (!$this->checkArguments(func_get_args())) {
            return false;
        }
        $select_fields = array('room_no', 'type', 'capacity', 'price', 'description');
        $where = "room_no = " . $room_no;
        $room_details = $this->getData('room', $select_fields, $where);
        if (!$this->getData('room', $select_fields, $where)) {
            return null;
        }
        return $room_details;
    }
    public function bookRoom($customer_info = null, $room_no = null, $from_date = null, $to_date = null)
    {
        if (!$this->checkArguments(func_get_args())) {
            return false;
        } else {
            if (!in_array($room_no, $this->listAvailableRooms($from_date, $to_date))) {
                return false;
            }
        }
        $amount = $this->calcBill($room_no, $from_date, $to_date);
        $insert_query = "insert into reservation (room_no, from_date, to_date, status, address_proof_id, amount) 
            VALUES (" . $room_no . ", '" . $from_date . "', '" . $to_date . "', '" . $customer_info['status'] .
            "', '" . $customer_info['address_proof_id'] . "', " . $amount . ")";
        if (!mysqli_query($this->connection, $insert_query)) {
            return null;
        }
        $reservation_id = mysqli_insert_id($this->connection);
        $add_customer_query = "insert into customer (address_proof_id, address_proof_type, user_name, address, 
            phone_no, email_id) values 
            ('" . $customer_info['address_proof_id'] . "', '" . $customer_info['address_proof_type'] . "', '" .
            $customer_info['user_name'] . "', '" . $customer_info['address'] . "', " . $customer_info['phone_no'] . ", '"
            . $customer_info['email_id'] . "')";
        if (mysqli_query($this->connection, $add_customer_query)) {
            return $reservation_id;
        } else {
            return false;
        }
    }
    public function cancelBooking($reservation_id = null, $address_proof_id = null)
    {
        if (!$this->checkArguments(func_get_args())) {
            return false;
        }
        $cancel_booking_query = "update reservation set status = 'cancelled' where
         address_proof_id = '" . $address_proof_id . "' and reservation_id = " . $reservation_id;
        //echo $cancel_booking_query;
        if (mysqli_query($this->connection, $cancel_booking_query)) {
            return true;
        }
        return false;
    }
    public function calcBill($room_no = null, $from_date = null, $to_date = null)
    {
        if (!$this->checkArguments(func_get_args())) {
            return false;
        }
        $price_data = [];
        $from_date = new DateTime($from_date);
        $to_date = new DateTime($to_date);
        if ($from_date == $to_date) {
            $no_of_days = 1;
        } else {
            $no_of_days = $to_date->diff($from_date)->format("%a");
        }

        $where = "room_no = "  . $room_no;
        $price_data = $this->getData('room', array('price'), $where);
        foreach ($price_data as $key => $price_value) {
            $price = $price_value['price'];
        }
        $total_bill = $price * $no_of_days;
        return $total_bill;
    }
    public function listRoomTypes()
    {
        $room_types = $this->getData('room', array('type'));
        if ($room_types) {
            return $room_types;
        } else {
            return false;
        }
    }
    public function listRoomCapacities()
    {
        $room_capacity = $this->getData('room', array('capacity'));
        if ($room_capacity) {
            return $room_capacity;
        } else {
            return false;
        }
    }
    public function getData($table = null, $select_fields = null, $where = null, $join = null)
    {
        if (!$this->checkArguments(func_get_args())) {
            return false;
        }
        $record = [];
        $select = "SELECT distinct ";
        $fields = implode(",", $select_fields);
        if ($where === null) {
            $query = $select . $fields . " FROM " . $table . $join;
        } else {
            $query = $select . $fields . " FROM " . $table . $join . " WHERE " . $where;
        }
        //echo $query . "<br>";
        if (mysqli_query($this->connection, $query)) {
            $result = mysqli_query($this->connection, $query);
            while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                array_push($record, $row);
            }
            //print_r($record);
            return $record;
        } else {
            return false;
        }
    }
    public function checkArguments($fn_args = null)
    {
        if (empty($fn_args)) {
            return false;
        }
        foreach ($fn_args as $key => $fn_arg) {
            if (is_array($fn_arg)) {
                $this->checkArguments($fn_arg);
            } else if (!isset($fn_arg)) {
                    return false;
            }
        }
        return true;
    }
}
