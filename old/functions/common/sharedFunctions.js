const admin = require('firebase-admin');
const deductFromWallet = require('./index').deductFromWallet;
const addToWallet = require('./index').addToWallet;

module.exports.UpdateBooking = (bookingData,order_id,transaction_id,gateway) => {
    let curChanges = {
        status: bookingData.status === 'PAYMENT_PENDING' ? 'NEW' : 'PAID',
        prepaid: bookingData.status === 'PAYMENT_PENDING'? true : false,
        transaction_id: transaction_id,
        gateway: gateway
    }
    Object.assign(curChanges, bookingData.paymentPacket);
    admin.database().ref('bookings').child(order_id).update(curChanges);
    if(bookingData.status === 'PENDING'){
        admin.database().ref('users').child(bookingData.driver).update({queue:false});
        addToWallet(bookingData.driver, bookingData.driver_share, order_id, order_id );
    }
    if(bookingData.status === 'PAYMENT_PENDING'){
        if(parseFloat(bookingData.paymentPacket.usedWalletMoney)>0){
            deductFromWallet(bookingData.customer, bookingData.paymentPacket.usedWalletMoney , order_id);
        }
    }
}