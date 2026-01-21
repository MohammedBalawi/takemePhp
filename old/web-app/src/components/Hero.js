import React from 'react';
import { Grid, Typography, Button, Box } from '@mui/material';
import backgroundImage from '../assets/img/background.jpg';
import useStyles from '../styles/styles';
import { useTranslation } from "react-i18next";
import { useNavigate } from 'react-router-dom';
import { checkCat } from '../common/sharedFunctions';
const Hero = (props) => {
  const classes = useStyles();
  const { t } = useTranslation();
  const navigate = useNavigate();
  const role = props.role;
  return (
    <Box className={classes.heroBox} sx={{ backgroundImage: `url(${backgroundImage})`, backgroundSize: 'cover' }}>
      <Grid container spacing={12} className={classes.gridContainer}>
        <Grid item xs={12} md={7}>
          <Typography variant="h3" fontWeight={500} className={classes.titleMain}>
          {t('book_your_title')}
          </Typography>
          <Typography variant="h6" className={classes.subtitleMain}>
           {t('about_us_content2')}
          </Typography>
          {role && role !== 'driver' && !checkCat(3)?
            <Button
              variant="contained"
              color="primary"
              sx={{ width: '200px', fontSize: '16px' }}
              onClick={(e) => { e.preventDefault(); navigate('/addbookings') }}
            >
            {t('book_your_ride_menu')}
            </Button>
          :null}
        </Grid>
      </Grid>
    </Box>
  );
};

export default Hero;