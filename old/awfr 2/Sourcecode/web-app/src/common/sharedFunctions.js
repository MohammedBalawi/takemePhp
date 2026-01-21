import React from 'react';
import moment from 'moment/min/moment-with-locales';
import {
  Grid,
  Typography,
  TextField,
  FormControlLabel,
  Switch
} from '@mui/material';
import { useTranslation } from "react-i18next";

export const checkCat = (cat) => cat === 2;

export const isParcelRequired = true;
export const isOptionsRequired = true;

export const bookingHistoryColumns = (role, settings, t, isRTL) => [
    { title: t('booking_id'), field: 'id', cellStyle: isRTL=== 'rtl' ? {paddingRight:220}:{paddingLeft:220}, headerStyle: isRTL=== 'rtl' ?{paddingRight:220}:{paddingLeft:220}, },
    { title: t('booking_ref'), field: 'reference', cellStyle:{textAlign:isRTL=== 'rtl' ?'right':'left'}  },
    { title: t('booking_date'), field: 'bookingDate', render: rowData => rowData.bookingDate?moment(rowData.bookingDate).format('lll'):null,cellStyle:{textAlign:isRTL=== 'rtl' ?'right':'left'}},
    { title: t('trip_start_time'), field: 'tripdate', render: rowData => rowData.tripdate?moment(rowData.tripdate).format('lll'):null,cellStyle:{textAlign:isRTL=== 'rtl' ?'right':'left'}},
    { title: t('car_type'), field: 'carType',cellStyle:{textAlign:isRTL=== 'rtl' ?'right':'left'}  },
    { title: t('customer_name'),field: 'customer_name',cellStyle:{textAlign:isRTL=== 'rtl' ?'right':'left'}  },
    { title: t('pickup_address'), field: 'pickupAddress', cellStyle:{textAlign:isRTL=== 'rtl' ?'right':'left'} },
    { title: t('drop_address'), field: 'dropAddress', cellStyle:{textAlign:isRTL=== 'rtl' ?'right':'left'} },
    { title: t('assign_driver'), field: 'driver_name',cellStyle:{textAlign:isRTL=== 'rtl' ?'right':'left'} },
    { title: t('deliveryPerson'), field: 'deliveryPerson', cellStyle:{textAlign:isRTL=== 'rtl' ?'right':'left'}  },
    { title: t('deliveryPersonPhone'), field: 'deliveryPersonPhone', cellStyle:{textAlign:isRTL=== 'rtl' ?'right':'left'} },
    { title: t('pickUpInstructions'), field: 'pickUpInstructions', cellStyle:{textAlign:isRTL=== 'rtl' ?'right':'left'} },
    { title: t('deliveryInstructions'), field: 'deliveryInstructions', cellStyle:{textAlign:isRTL=== 'rtl' ?'right':'left'}  },
    { title: t('parcel_type'), render: rowData => <span>{rowData.parcelTypeSelected?rowData.parcelTypeSelected.description + " (" + rowData.parcelTypeSelected.amount + ")":""}</span> , cellStyle:{textAlign:isRTL=== 'rtl' ?'right':'left'} },
    { title: t('parcel_option'), render: rowData => <span>{rowData.optionSelected?rowData.optionSelected.description + " (" + rowData.optionSelected.amount + ")":""}</span>, cellStyle:{textAlign:isRTL=== 'rtl' ?'right':'left'} },
    { title: t('booking_status'), field: 'status', render: rowData => <span>{t(rowData.status)}</span>,cellStyle:{textAlign:isRTL=== 'rtl' ?'right':'left'}  },
    { title: t('take_pickup_image'),  field: 'pickup_image',render: rowData => rowData.pickup_image?<img alt='Pick Up' src={rowData.pickup_image} style={{width: 150}}/>:null, editable:'never'},
    { title: t('take_deliver_image'),  field: 'deliver_image',render: rowData => rowData.deliver_image?<img alt='Deliver' src={rowData.deliver_image} style={{width: 150}}/>:null, editable:'never'},
    { title: t('cancellation_reason'), field: 'reason',cellStyle:{textAlign:isRTL=== 'rtl' ?'right':'left'} },
    { title: t('cancellationFee'), render: rowData => <span>{rowData.cancellationFee? rowData.cancellationFee :(0).toFixed(settings.decimal)}</span>, cellStyle:{paddingLeft: isRTL=== 'rtl'?40:null}},
    { title: t('otp'), field: 'otp',cellStyle:{textAlign:isRTL=== 'rtl' ?'right':'left'}},
    { title: t('trip_cost'), field: 'trip_cost',cellStyle:{textAlign:isRTL=== 'rtl' ?'right':'left'} },
    { title: t('trip_start_time'), field: 'trip_start_time',cellStyle:{textAlign:isRTL=== 'rtl' ?'right':'left'} },
    { title: t('trip_end_time'), field: 'trip_end_time',cellStyle:{textAlign:isRTL=== 'rtl' ?'right':'left'} },
    { title: t('total_time'), field: 'total_trip_time',cellStyle:{textAlign:isRTL=== 'rtl' ?'right':'left'} },
    { title: t('distance'), field: 'distance',cellStyle:{textAlign:isRTL=== 'rtl' ?'right':'left'} },
    { title: t('vehicle_no'), field: 'vehicle_number',cellStyle:{textAlign:isRTL=== 'rtl' ?'right':'left'} },  
    { title: t('trip_cost_driver_share'), hidden: role==='customer'? true: false, field: 'driver_share',cellStyle:{textAlign:isRTL=== 'rtl' ?'right':'left'}},
    { title: t('convenience_fee'), hidden: role==='customer'? true: false, field: 'convenience_fees',cellStyle:{textAlign:isRTL=== 'rtl' ?'right':'left'}},
    { title: t('discount_ammount'), field: 'discount',cellStyle:{textAlign:isRTL=== 'rtl' ?'right':'left'}},      
    { title: t('Customer_paid'), field: 'customer_paid',cellStyle:{textAlign:isRTL=== 'rtl' ?'right':'left'}},
    { title: t('payment_mode'), field: 'payment_mode',cellStyle:{textAlign:isRTL=== 'rtl' ?'right':'left'} },
    { title: t('payment_gateway'), field: 'gateway',cellStyle:{textAlign:isRTL=== 'rtl' ?'right':'left'} },
    { title: t('cash_payment_amount'), field: 'cashPaymentAmount',cellStyle:{textAlign:isRTL=== 'rtl' ?'right':'left'}},
    { title: t('card_payment_amount'), field: 'cardPaymentAmount',cellStyle:{textAlign:isRTL=== 'rtl' ?'right':'left'}},
    { title: t('wallet_payment_amount'), field: 'usedWalletMoney',cellStyle:{textAlign:isRTL=== 'rtl' ?'right':'left'}}
];

export const BookingModalBody = (props) => {
    const { t, i18n  } = useTranslation();
    const isRTL = i18n.dir();
    const { classes, instructionData, handleChange } = props;
    return (
      <Grid item xs={12}>
        <span>
            <Typography component="h2" variant="h5" style={{marginTop:15, color:'#000'}}>
                {t('delivery_information')}
            </Typography>
            <Grid item xs={12}>
              <TextField
                variant="outlined"
                margin="normal"
                fullWidth
                id="deliveryPerson"
                label={t('deliveryPerson')}
                name="deliveryPerson"
                autoComplete="deliveryPerson"
                onChange={handleChange}
                value={instructionData.deliveryPerson}
                autoFocus
                className={isRTL==='rtl'?classes.inputRtl:null}
                style={{direction:isRTL==='rtl'?'rtl':'ltr'}}
              />
            </Grid>
            <Grid item xs={12}>
              <TextField
                variant="outlined"
                margin="normal"
                fullWidth
                id="deliveryPersonPhone"
                label={t('deliveryPersonPhone')}
                name="deliveryPersonPhone"
                autoComplete="deliveryPersonPhone"
                onChange={handleChange}
                value={instructionData.deliveryPersonPhone}
                className={isRTL==='rtl'?[classes.inputRtl, classes.rightRty]:null}
                style={{direction:isRTL==='rtl'?'rtl':'ltr'}}
              />
            </Grid>
            <Grid item xs={12}>
              <TextField
                variant="outlined"
                margin="normal"
                fullWidth
                id="pickUpInstructions"
                label={t('pickUpInstructions')}
                name="pickUpInstructions"
                autoComplete="pickUpInstructions"
                onChange={handleChange}
                value={instructionData.pickUpInstructions}
                className={isRTL==='rtl'?classes.inputRtl:null}
                style={{direction:isRTL==='rtl'?'rtl':'ltr'}}
              />
            </Grid>
            <Grid item xs={12}>
              <TextField
                variant="outlined"
                margin="normal"
                fullWidth
                id="deliveryInstructions"
                label={t('deliveryInstructions')}
                name="deliveryInstructions"
                autoComplete="deliveryInstructions"
                onChange={handleChange}
                value={instructionData.deliveryInstructions}
                className={isRTL==='rtl'?classes.inputRtl:null}
                style={{direction:isRTL==='rtl'?'rtl':'ltr'}}
              />
            </Grid>
        </span>
      </Grid>
    )
}

export const validateBookingObj = (t, bookingObject, instructionData) => {
  const regx1 = /([0-9\s-]{7,})(?:\s*(?:#|x\.?|ext\.?|extension)\s*(\d+))?$/;
  if (/\S/.test(instructionData.deliveryPerson) && regx1.test(instructionData.deliveryPersonPhone) && instructionData.deliveryPersonPhone && instructionData.deliveryPersonPhone.length > 6) {
    bookingObject['instructionData'] = instructionData;
    return { bookingObject };
  } else {
    return { error: true, msg : t('deliveryDetailMissing')}
  }
}

export const PanicSettings = (props) => {
  return null;
}

export const DispatchSettings = (props) => {
  const { t, i18n } = useTranslation();
  const isRTL = i18n.dir();
  const { autoDispatch, onChange } = props;
  return (
      <FormControlLabel
          style={{ flexDirection: isRTL === 'rtl' ? 'row-reverse' : 'row' }}
          control={
              <Switch
                  checked={autoDispatch}
                  onChange={onChange}
                  name="autoDispatch"
                  color="primary"
              />
          }
      label={t('auto_dispatch')}
    />
  )
}

export const BookingImageSettings = (props) => {
  const { t, i18n } = useTranslation();
  const isRTL = i18n.dir();
  const { data, handleSwitchChange } = props;
  return (
      <span>
          <FormControlLabel
            style={{ marginTop: '10px', flexDirection: isRTL === 'rtl' ? 'row-reverse' : 'row' }}
            control={
              <Switch
                checked={data.AllowDeliveryPickupImageCapture}
                onChange={handleSwitchChange}
                name="AllowDeliveryPickupImageCapture"
                color="primary"
              />
            }
            label={t('allow_del_pkp_img')}
          />
          <FormControlLabel
            style={{ marginTop: '10px', flexDirection: isRTL === 'rtl' ? 'row-reverse' : 'row' }}
            control={
              <Switch
                checked={data.AllowFinalDeliveryImageCapture}
                onChange={handleSwitchChange}
                name="AllowFinalDeliveryImageCapture"
                color="primary"
              />
            }
            label={t('allow_del_final_img')}
          />
      </span>
  )
}

export const carTypeColumns = (t, isRTL, onClick) =>  [
  { title: t('name'), field: 'name', cellStyle:isRTL ==='rtl'? {paddingRight: 180 , textAlign: 'right' }:{ paddingLeft: 180}, headerStyle:isRTL==='rtl'?{paddingRight: 180}:{ paddingLeft: 180}},
  { title: t('image'),  field: 'image',cellStyle:{ textAlign: 'center'},
    initialEditValue: 'https://cdn.pixabay.com/photo/2012/04/15/22/09/car-35502__480.png',
    render: rowData => rowData.image? <button onClick={()=>{onClick(rowData)}}><img alt='CarImage' src={rowData.image} style={{width: 50}}/></button>:null
  },
  { title: t('base_fare'), field: 'base_fare',  type: 'numeric', cellStyle:{ textAlign: 'center'}},
  { title: t('rate_per_unit_distance'), field: 'rate_per_unit_distance',   type: 'numeric', cellStyle:{ textAlign: 'center'}},
  { title: t('rate_per_hour'), field: 'rate_per_hour', type: 'numeric',   cellStyle:{ textAlign: 'center'}},
  { title: t('min_fare'), field: 'min_fare', type: 'numeric',  cellStyle:{ textAlign: 'center'}},
  { title: t('convenience_fee'), field: 'convenience_fees', type: 'numeric', cellStyle:{ textAlign: 'center'}},
  {
    title: t('convenience_fee_type'),
    field: 'convenience_fee_type',
    lookup: { flat: t('flat'), percentage: t('percentage')},
    cellStyle:{ textAlign: 'center'}
  },
  { title: t('extra_info'), field: 'extra_info' , cellStyle:{ textAlign:isRTL ==='rtl'? 'right' : 'left'}}
];

export const acceptBid = (selectedBooking, selectedBidder) => {
  return null;
}

export const BidModal = (props) => {
  return null
}

export const  downloadCsv = (data, fileName) => {
  const finalFileName = fileName.endsWith(".csv") ? fileName : `${fileName}.csv`;
  const a = document.createElement("a");
  a.href = URL.createObjectURL(new Blob([data], { type: "text/csv" }));
  a.setAttribute("download", finalFileName);
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
}