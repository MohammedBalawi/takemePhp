import { firebase } from "../config/configureFirebase";
import { GetDistance } from "../other/GeoFunctions";

export const formatBookingObject = (bookingData, settings) => {
  const c = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'
  const reference = [...Array(6)].map(_ => c[~~(Math.random()*c.length)]).join('');
  let today = new Date().getTime();

  let pickUp = { lat: bookingData.pickup.coords.lat, lng: bookingData.pickup.coords.lng, add: bookingData.pickup.description };
  let drop = { lat: bookingData.drop.coords.lat, lng: bookingData.drop.coords.lng, add: bookingData.drop.description };
  
  let coords = [{latitude: pickUp.lat,longitude: pickUp.lng}];
  if(bookingData.drop.waypointsStr){
      bookingData.drop.waypointsStr.split("|").forEach((point)=>{
          let lat = point.split(',')[0];
          let lng = point.split(',')[1];
          coords.push({latitude: parseFloat(lat), longitude: parseFloat(lng)});
      });
  }
  coords.push({latitude: drop.lat,longitude: drop.lng});
  
  var otp;
  if(bookingData.settings.otp_secure)
      otp = Math.floor(Math.random() * 90000) + 10000;
  else{
      otp = false;
  }
  
  return {
      carType: bookingData.carDetails.name,
      carImage: bookingData.carDetails.image,
      customer: bookingData.userDetails.uid,
      commission_type: bookingData.carDetails.convenience_fee_type,
      commission_rate: bookingData.carDetails.convenience_fees,
      reference: reference,
      customer_email: bookingData.userDetails.email,
      customer_name: bookingData.userDetails.firstName + ' ' + bookingData.userDetails.lastName,
      customer_contact: bookingData.userDetails.mobile? bookingData.userDetails.mobile: ' ',
      customer_token: bookingData.userDetails.pushToken? bookingData.userDetails.pushToken: ' ',
      customer_image: bookingData.userDetails.profile_image ? bookingData.userDetails.profile_image : "",
      drop: drop,
      pickup: pickUp,
      estimate: bookingData.estimate.estimateFare,
      estimateDistance: bookingData.estimate.estimateDistance,
      distance: bookingData.estimate.estimateDistance,
      estimateTime:bookingData.estimate.estimateTime,
      status: bookingData.booking_type_admin ? "NEW" : "PAYMENT_PENDING",
      bookLater:bookingData.bookLater,
      tripdate: bookingData.bookLater?bookingData.tripdate:today,
      bookingDate: today,
      otp: otp,
      booking_type_admin:bookingData.booking_type_admin,
      coords: coords,
      waypoints: bookingData.drop.waypoints? bookingData.drop.waypoints: null,
      roundTrip: bookingData.roundTrip? bookingData.roundTrip:null,
      tripInstructions: bookingData.tripInstructions? bookingData.tripInstructions: null,
      trip_cost: bookingData.estimate.estimateFare,
      convenience_fees: bookingData.estimate.convenience_fees,
      driver_share: (parseFloat(bookingData.estimate.estimateFare) - parseFloat(bookingData.estimate.convenience_fees)).toFixed(settings.decimal),
      paymentPacket: bookingData.paymentPacket? bookingData.paymentPacket : null,
      requestedDrivers: bookingData.requestedDrivers?  bookingData.requestedDrivers: null,
      driverEstimates: bookingData.driverEstimates?  bookingData.driverEstimates: null,
      ...bookingData.instructionData,
      fleetadmin: bookingData.fleetadmin? bookingData.fleetadmin:null
  }
}

export const saveAddresses = async (booking, driverLocation) => {
  const { singleUserRef } = firebase;
  let address = booking.drop.add;

  singleUserRef(booking.customer)
    .child("savedAddresses")
    .once("value", (savedAdd) => {
      if (savedAdd.val()) {
        let addresses = savedAdd.val();
        let didNotMatch = true;
        for (let key in addresses) {
          let entry = addresses[key];
          if (
            GetDistance(
              entry.lat,
              entry.lng,
              booking.drop.lat,
              booking.drop.lng
            ) < 0.1
          ) {
            didNotMatch = false;
            let count = entry.count ? entry.count + 1 : 1;
            singleUserRef(booking.customer)
              .child("savedAddresses/" + key)
              .update({ count: count });
            break;
          }
        }
        if (didNotMatch) {
          singleUserRef(booking.customer).child("savedAddresses").push({
            description: address,
            lat: booking.drop.lat,
            lng: booking.drop.lng,
            count: 1,
          });
        }
      } else {
        singleUserRef(booking.customer)
          .child("savedAddresses")
          .push({
            description: address,
            lat: booking.drop.lat,
            lng:  booking.drop.lng,
            count: 1,
          });
      }
    });
  return address;
};

export const addActualsToBooking = async (booking, address, driverLocation) => {
  const end_time = new Date();
  const diff = (end_time.getTime() - parseFloat(booking.startTime)) / 1000;
  const totalTimeTaken = Math.abs(Math.round(diff));
  booking.trip_end_time = end_time.getHours() + ":" + end_time.getMinutes() + ":" + end_time.getSeconds();
  booking.endTime = end_time.getTime();
  booking.total_trip_time = totalTimeTaken;
  return booking;
};

export const updateDriverQueue = (booking) => {
  return booking;
};

