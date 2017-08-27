<?php
require_once "config.php";
require_once "Hotel.php";

class HotelTest extends PHPUnit_Framework_TestCase
{
    private $hotel_obj;
    public function __construct()
    {
        $this->hotel_obj = new Hotel();
    }
    public function testGetRoomDetails()
    {
        $room_details = array(
            0 => array (
                'room_no' => '101',
                'type' => 'deluxe',
                'capacity' => '1',
                'price' => '1000',
                'description' => 'Deluxe room with single bed and opulent bathroom with oversized jetted tub, raised ceilings, private balcony.'
                )
            );
        $this->expectOutputString($this->hotel_obj->getRoomDetails(101));
        print_r($room_details);
        $this->assertNull($this->hotel_obj->getRoomDetails(105));
        $this->assertFalse($this->hotel_obj->getRoomDetails());
    }
    public function testAllRooms()
    {
        $this->assertCount(1, $this->hotel_obj->listAllRooms('deluxe', 1, 1000));
        $this->assertCount(3, $this->hotel_obj->listAllRooms());
        $this->assertNull($this->hotel_obj->listAllRooms('unavailable', 1, 1000));
    }
    public function testListAvailableRooms()
    {
        $avaibale_rooms1 = $this->hotel_obj->listAvailableRooms('2015-11-03', '2016-11-05', 'executive suite');
        $avaibale_rooms2 = $this->hotel_obj->listAvailableRooms('2016-11-03', '2016-11-05', 'executive suite');
        $avaibale_rooms3 = $this->hotel_obj->listAvailableRooms('2016-11-03', '2016-11-05', 'unavailable');
        $avaibale_rooms4 = $this->hotel_obj->listAvailableRooms('2016-11-03', '2016-11-05');
        $this->assertCount(1, $avaibale_rooms1);
        $this->assertCount(2, $avaibale_rooms2);
        $this->assertCount(3, $avaibale_rooms4);
        $this->assertNull($avaibale_rooms3);
        $this->assertFalse($this->hotel_obj->listAvailableRooms());
    }
    public function testCalcBill()
    {
        $amount1 = $this->hotel_obj->calcBill(102, '2015-12-03', '2015-12-10');
        $amount2 = $this->hotel_obj->calcBill(102, '2015-12-03', '2015-12-03');
        $this->expectOutputString($amount1);
        print(21000);
        echo "\n";
        $this->expectOutputString($amount2);
        print(3000);
        $this->assertFalse($this->hotel_obj->calcBill());
    }
    public function testBookRoom()
    {
        $customer_info['address_proof_id'] = "vc101203";
        $customer_info['address_proof_type'] = "voters card";
        $customer_info['user_name'] = "Manu";
        $customer_info['phone_no'] = 456987;
        $customer_info['email_id'] = "manu@manu.com";
        $customer_info['address'] = "addresssss";
        $customer_info['status'] = "booked";
        $this->assertNotNull($this->hotel_obj->bookRoom($customer_info, 101, '2016-12-03', '2016-12-10'));
        $this->assertFalse($this->hotel_obj->bookRoom());

    }
    public function testCancelBooking()
    {
        $this->assertTrue($this->hotel_obj->cancelBooking(88, "vc101201"));
        $this->assertFalse($this->hotel_obj->cancelBooking());
    }
    public function testListRoomTypes()
    {
        $this->assertCount(2, $this->hotel_obj->listRoomTypes());
    }
    public function testRoomCapacities()
    {
        $this->assertCount(3, $this->hotel_obj->listRoomCapacities());
    }
    public function testGetData()
    {
        $price_data = $this->hotel_obj->getData('room', array('price'), "room_no = 102");
        $this->assertEquals(3000, $price_data[0]['price']);
        $this->assertFalse($this->hotel_obj->getData());
    }
    public function testCheckArguments()
    {
        $this->assertTrue($this->hotel_obj->checkArguments(array(1, "sample data")));
        $this->assertTrue($this->hotel_obj->checkArguments(array(array(1, "sample array data"))));
        $this->assertFalse($this->hotel_obj->checkArguments(null));
        $this->assertFalse($this->hotel_obj->checkArguments(array(null)));
        $this->assertFalse($this->hotel_obj->checkArguments());
    }
}
