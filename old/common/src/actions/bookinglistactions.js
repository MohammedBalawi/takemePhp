import {
  FETCH_BOOKINGS,
  FETCH_BOOKINGS_SUCCESS,
  FETCH_BOOKINGS_FAILED,
  UPDATE_BOOKING,
  CANCEL_BOOKING
} from "../store/types";
import { fetchBookingLocations } from '../actions/locationactions';
import { RequestPushMsg } from '../other/NotificationFunctions';
import store from '../store/store';
import { firebase } from '../config/configureFirebase';
import { addActualsToBooking, saveAddresses, updateDriverQueue } from "../other/sharedFunctions";

export const fetchBookings = () => (dispatch) => {

  const {
    bookingListRef,
  } = firebase;

  dispatch({
    type: FETCH_BOOKINGS,
    payload: null,
  });

  const userInfo = store.getState().auth.profile;

  bookingListRef(userInfo.uid, userInfo.usertype).off();
  bookingListRef(userInfo.uid, userInfo.usertype).on("value", (snapshot) => {
    if (snapshot.val()) {
      const data = snapshot.val();
      const active = [];
      let tracked = null;
      const bookings = Object.keys(data)
        .map((i) => {
          data[i].id = i;
          data[i].pickupAddress = data[i].pickup.add;
          data[i].dropAddress = data[i].drop.add;
          data[i].discount = data[i].discount
            ? data[i].discount
            : 0;
          data[i].cashPaymentAmount = data[i].cashPaymentAmount
            ? data[i].cashPaymentAmount
            : 0;
          data[i].cardPaymentAmount = data[i].cardPaymentAmount
            ? data[i].cardPaymentAmount
            : 0;
          return data[i];
        });
      for (let i = 0; i < bookings.length; i++) {
        if (['PAYMENT_PENDING','NEW', 'ACCEPTED', 'ARRIVED', 'STARTED', 'REACHED', 'PENDING', 'PAID'].indexOf(bookings[i].status) != -1) {
          active.push(bookings[i]);
        }
        if ((['ACCEPTED', 'ARRIVED', 'STARTED'].indexOf(bookings[i].status) != -1) && userInfo.usertype == 'driver') {
          tracked = bookings[i];
          fetchBookingLocations(tracked.id)(dispatch);
        }
      }
      dispatch({
        type: FETCH_BOOKINGS_SUCCESS,
        payload: {
          bookings: bookings.reverse(),
          active: active,
          tracked: tracked
        },
      });
      if (tracked) {
        dispatch({
          type: FETCH_BOOKINGS_SUCCESS,
          payload: null
        });
      }
    } else {
      dispatch({
        type: FETCH_BOOKINGS_FAILED,
        payload: store.getState().languagedata.defaultLanguage.no_bookings,
      });
    }
  });
};

export const updateBooking = (booking) => async (dispatch) => {

  const {
    auth,
    trackingRef,
    singleBookingRef,
    singleUserRef,
    walletHistoryRef,
    settingsRef,
    userRatingsRef
  } = firebase;

  dispatch({
    type: UPDATE_BOOKING,
    payload: booking,
  });
  
  if (booking.status == 'PAYMENT_PENDING') {
    singleBookingRef(booking.id).update(booking);
  }
  if (booking.status == 'NEW') {
    singleBookingRef(booking.id).update(updateDriverQueue(booking));
  }
  if (booking.status == 'ACCEPTED') {
    singleBookingRef(booking.id).update(updateDriverQueue(booking));
  }
  if (booking.status == 'ARRIVED') {
    let dt = new Date();
    booking.driver_arrive_time = dt.getTime().toString();
    singleBookingRef(booking.id).update(booking);
    RequestPushMsg(
      booking.customer_token,
      {
          title: store.getState().languagedata.defaultLanguage.notification_title,
          msg: store.getState().languagedata.defaultLanguage.driver_near,
          screen: 'BookedCab',
          params: { bookingId: booking.id }
      });
  }
  if (booking.status == 'STARTED') {
    let dt = new Date();
    let localString = dt.getHours() + ":" + dt.getMinutes() + ":" + dt.getSeconds();
    let timeString = dt.getTime();
    booking.trip_start_time = localString;
    booking.startTime = timeString;
    singleBookingRef(booking.id).update(booking);

    const driverLocation = store.getState().gpsdata.location;
    
    trackingRef(booking.id).push({
      at: new Date().getTime(),
      status: 'STARTED',
      lat: driverLocation.lat,
      lng: driverLocation.lng
    });

    RequestPushMsg(
      booking.customer_token,
      {
          title: store.getState().languagedata.defaultLanguage.notification_title,
          msg: store.getState().languagedata.defaultLanguage.driver_journey_msg + booking.reference,
          screen: 'BookedCab',
          params: { bookingId: booking.id }
      });
  }
  if (booking.status == 'REACHED') {

    const driverLocation = store.getState().gpsdata.location;

    trackingRef(booking.id).push({
      at: new Date().getTime(),
      status: 'REACHED',
      lat: driverLocation.lat,
      lng: driverLocation.lng
    });

    let address = await saveAddresses(booking,driverLocation);

    let bookingObj = await addActualsToBooking(booking, address, driverLocation);
    singleBookingRef(booking.id).update(bookingObj);
    RequestPushMsg(
      booking.customer_token,
      {
          title: store.getState().languagedata.defaultLanguage.notification_title,
          msg: store.getState().languagedata.defaultLanguage.driver_completed_ride,
          screen: 'BookedCab',
          params: { bookingId: booking.id }
      });
  }
  if (booking.status == 'PENDING') {
    singleBookingRef(booking.id).update(booking);
    singleUserRef(booking.driver).update({ queue: false });
  }
  if (booking.status == 'PAID') {
    const settingsdata = await settingsRef.once("value");
    const settings = settingsdata.val();
    singleBookingRef(booking.id).update(booking);
    if(booking.driver == auth.currentUser.uid && (booking.prepaid || booking.payment_mode == 'cash' || booking.payment_mode == 'wallet')){
      singleUserRef(booking.driver).update({ queue: false });
    }

    singleUserRef(booking.driver).once('value', snapshot => {
      let walletBalance = parseFloat(snapshot.val().walletBalance);
      walletBalance = walletBalance + parseFloat(booking.driver_share);
      if(parseFloat(booking.cashPaymentAmount)>0){
        walletBalance = walletBalance - parseFloat(booking.cashPaymentAmount);
      }
      singleUserRef(booking.driver).update({"walletBalance": parseFloat(walletBalance.toFixed(settings.decimal))});

      let details = {
        type: 'Credit',
        amount: parseFloat(booking.driver_share).toFixed(settings.decimal),
        date: new Date().getTime(),
        txRef: booking.id
      }
      walletHistoryRef(booking.driver).push(details);
      
      if(parseFloat(booking.cashPaymentAmount)>0){
        let details = {
          type: 'Debit',
          amount: booking.cashPaymentAmount,
          date: new Date().getTime(),
          txRef: booking.id
        }
        walletHistoryRef(booking.driver).push(details);
      }  
    });

    RequestPushMsg(
      booking.customer_token,
      {
          title: store.getState().languagedata.defaultLanguage.notification_title,
          msg: store.getState().languagedata.defaultLanguage.success_payment,
          screen: 'BookedCab',
          params: { bookingId: booking.id }
      });
      RequestPushMsg(
        booking.driver_token,
        {
            title: store.getState().languagedata.defaultLanguage.notification_title,
            msg: store.getState().languagedata.defaultLanguage.success_payment,
            screen: 'BookedCab',
            params: { bookingId: booking.id }
        });
  }
  if (booking.status == 'COMPLETE') {
    singleBookingRef(booking.id).update(booking);
    if (booking.rating) {
      RequestPushMsg(
        booking.driver_token,
        {
            title: store.getState().languagedata.defaultLanguage.notification_title,
            msg:  store.getState().languagedata.defaultLanguage.received_rating.toString().replace("X", booking.rating.toString()),
            screen: 'BookedCab',
            params: { bookingId: booking.id }
        });
      
      userRatingsRef(booking.driver).once('value', snapshot => {
        let ratings = snapshot.val();
        let rating;
        if(ratings){
          let sum = 0;
          const arr = Object.values(ratings);
          for (let i = 0; i< arr.length ; i++){
            sum = sum + arr[i].rate
          }
          sum = sum + booking.rating;
          rating = parseFloat(sum / (arr.length + 1)).toFixed(1);
        }else{
          rating =  booking.rating;
        }
        singleUserRef(booking.driver).update({rating: rating});
        userRatingsRef(booking.driver).push({
          user: booking.customer,
          rate: booking.rating
        });
      });
    }
  }
};

export const cancelBooking = (data) => (dispatch) => {
  const {
    singleBookingRef,
    singleUserRef,
    requestedDriversRef
  } = firebase;

  dispatch({
    type: CANCEL_BOOKING,
    payload: data,
  });

  singleBookingRef(data.booking.id).update({
    status: 'CANCELLED',
    reason: data.reason,
    cancelledBy: data.cancelledBy
  }).then(() => {
    if (data.booking.driver && (data.booking.status === 'ACCEPTED' || data.booking.status === 'ARRIVED')) {
      singleUserRef(data.booking.driver).update({ queue: false });
      RequestPushMsg(
        data.booking.driver_token,
        {
            title: store.getState().languagedata.defaultLanguage.notification_title,
            msg:  store.getState().languagedata.defaultLanguage.booking_cancelled + data.booking.id,
            screen: 'BookedCab',
            params: { bookingId: data.booking.id }
        });
      RequestPushMsg(
        data.booking.customer_token,
        {
            title: store.getState().languagedata.defaultLanguage.notification_title,
            msg:  store.getState().languagedata.defaultLanguage.booking_cancelled + data.booking.id,
            screen: 'BookedCab',
            params: { bookingId: data.booking.id }
        });
    }
    if (data.booking.status === 'NEW') {
      requestedDriversRef(data.booking.id).remove();
    }
  });
};

export const updateBookingImage = (booking, imageType, imageBlob) => (dispatch) => {
  const   {
    singleBookingRef,
    bookingImageRef
  } = firebase;
  bookingImageRef(booking.id,imageType).put(imageBlob).then(() => {
    imageBlob.close()
    return bookingImageRef(booking.id,imageType).getDownloadURL()
  }).then((url) => {
    if(imageType == 'pickup_image'){
      booking.pickup_image = url;
    }
    if(imageType == 'deliver_image'){
      booking.deliver_image = url;
    }
    singleBookingRef(booking.id).update(booking);
    dispatch({
      type: UPDATE_BOOKING,
      payload: booking,
    });
  })
};

export const forceEndBooking = (booking) => async (dispatch) => {

  const {
    trackingRef,
    singleBookingRef,
    singleUserRef,
    walletHistoryRef,
    settingsRef,
  } = firebase;

  dispatch({
    type: UPDATE_BOOKING,
    payload: booking,
  });
  
  if (booking.status == 'STARTED') {

    trackingRef(booking.id).push({
      at: new Date().getTime(),
      status: 'REACHED',
      lat: booking.drop.lat,
      lng: booking.drop.lng
    });

    const end_time = new Date();
    const diff = (end_time.getTime() - parseFloat(booking.startTime)) / 1000;
    const totalTimeTaken = Math.abs(Math.round(diff));
    booking.trip_end_time = end_time.getHours() + ":" + end_time.getMinutes() + ":" + end_time.getSeconds();
    booking.endTime = end_time.getTime();
    booking.total_trip_time = totalTimeTaken;

    RequestPushMsg(
      booking.customer_token,
      {
          title: store.getState().languagedata.defaultLanguage.notification_title,
          msg: store.getState().languagedata.defaultLanguage.driver_completed_ride,
          screen: 'BookedCab',
          params: { bookingId: booking.id }
      });

    singleUserRef(booking.driver).update({ queue: false });

    if(booking.prepaid){

      const settingsdata = await settingsRef.once("value");
      const settings = settingsdata.val();

      singleUserRef(booking.driver).once('value', snapshot => {
        let walletBalance = parseFloat(snapshot.val().walletBalance);
        walletBalance = walletBalance + parseFloat(booking.driver_share);
        if(parseFloat(booking.cashPaymentAmount)>0){
          walletBalance = walletBalance - parseFloat(booking.cashPaymentAmount);
        }
        singleUserRef(booking.driver).update({"walletBalance": parseFloat(walletBalance.toFixed(settings.decimal))});

        let details = {
          type: 'Credit',
          amount: parseFloat(booking.driver_share).toFixed(settings.decimal),
          date: new Date().getTime(),
          txRef: booking.id
        }
        walletHistoryRef(booking.driver).push(details);
        
        if(parseFloat(booking.cashPaymentAmount)>0){
          let details = {
            type: 'Debit',
            amount: booking.cashPaymentAmount,
            date: new Date().getTime(),
            txRef: booking.id
          }
          walletHistoryRef(booking.driver).push(details);
        }  
      });

      RequestPushMsg(
        booking.customer_token,
        {
            title: store.getState().languagedata.defaultLanguage.notification_title,
            msg: store.getState().languagedata.defaultLanguage.success_payment,
            screen: 'BookedCab',
            params: { bookingId: booking.id }
        });
        RequestPushMsg(
          booking.driver_token,
          {
              title: store.getState().languagedata.defaultLanguage.notification_title,
              msg: store.getState().languagedata.defaultLanguage.success_payment,
              screen: 'BookedCab',
              params: { bookingId: booking.id }
          });
      booking.status = 'PAID';
    } else{
      booking.status = 'PENDING';
    }

    singleBookingRef(booking.id).update(booking);
  }
};