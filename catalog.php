<?php
// All table/column identifiers are fixed here; user input is never used as an SQL identifier.
$catalog = [
 'passenger'=>['title'=>'Passengers','pk'=>['passenger_id'],'fields'=>[
  'first_name'=>['First name','text',true], 'second_name'=>['Middle/second name','text',false], 'last_name'=>['Last name','text',true],
  'phone_number'=>['Phone number','text',false], 'email_address'=>['Email address','email',false], 'address_line1'=>['Address line 1','text',false], 'address_line2'=>['Address line 2','text',false], 'city'=>['City','text',false], 'state_province_county'=>['State/province/county','text',false], 'country'=>['Country','text',false], 'other_details'=>['Other details','textarea',false]]],
 'booking_agent'=>['title'=>'Booking Agents','pk'=>['agent_id'],'fields'=>[
  'agent_name'=>['Agent name','text',true], 'phone_number'=>['Phone number','text',false], 'email_address'=>['Email address','email',false], 'agency_name'=>['Agency name','text',false], 'other_details'=>['Other details','textarea',false]]],
 'reservation'=>['title'=>'Reservations','pk'=>['reservation_id'],'fields'=>[
  'passenger_id'=>['Passenger','fk:passenger:passenger_id:first_name,last_name',true], 'agent_id'=>['Booking agent (optional)','fk:booking_agent:agent_id:agent_name',false],
  'reservation_status_code'=>['Reservation status code','text',true], 'ticket_type_code'=>['Ticket type code','text',true], 'travel_class_code'=>['Travel class code','text',true], 'created_at_utc'=>['Created at (UTC)','datetime-local',true], 'party_size'=>['Party size','number',true]]],
 'payment'=>['title'=>'Payments','pk'=>['payment_id'],'fields'=>[
  'payment_status_code'=>['Payment status code','text',true], 'payment_date_utc'=>['Payment date (UTC)','datetime-local',true], 'payment_amount'=>['Amount','number:0.01',true]]],
 'airport'=>['title'=>'Airports','pk'=>['airport_code'],'fields'=>[
  'airport_code'=>['Airport code (3 letters)','text',true], 'airport_name'=>['Airport name','text',true], 'city'=>['City','text',true], 'country'=>['Country','text',true], 'other_details'=>['Other details','textarea',false]]],
 'flight_schedule'=>['title'=>'Flight Schedules','pk'=>['flight_number'],'fields'=>[
  'flight_number'=>['Flight number','text',true], 'airline_code'=>['Airline code','text',true], 'usual_aircraft_type_code'=>['Usual aircraft type code','text',true],
  'origin_airport_code'=>['Origin airport','fk:airport:airport_code:airport_name',true], 'destination_airport_code'=>['Destination airport','fk:airport:airport_code:airport_name',true], 'scheduled_departure_utc'=>['Scheduled departure (UTC)','datetime-local',true], 'scheduled_arrival_utc'=>['Scheduled arrival (UTC)','datetime-local',true]]],
 'flight_leg'=>['title'=>'Flight Legs','pk'=>['leg_id'],'fields'=>[
  'flight_number'=>['Flight schedule','fk:flight_schedule:flight_number:flight_number',true], 'origin_airport_code'=>['Origin airport','fk:airport:airport_code:airport_name',true], 'destination_airport_code'=>['Destination airport','fk:airport:airport_code:airport_name',true], 'actual_departure_utc'=>['Actual departure (UTC)','datetime-local',false], 'actual_arrival_utc'=>['Actual arrival (UTC)','datetime-local',false]]],
 'flight_cost'=>['title'=>'Flight Costs','pk'=>['flight_number','aircraft_type_code','valid_from'],'fields'=>[
  'flight_number'=>['Flight schedule','fk:flight_schedule:flight_number:flight_number',true], 'aircraft_type_code'=>['Aircraft type code','text',true], 'valid_from'=>['Valid from','date',true], 'valid_to'=>['Valid to','date',true], 'cost_amount'=>['Cost amount','number:0.01',true]]],
];
$referenceTables = ['calendar_date'=>'Calendar dates'];
