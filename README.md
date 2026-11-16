# abisu

Ab!SU is a weather advisory and class suspension system developed for ITE 18 - APPLICATIONS DEVELOPMENT AND EMERGING TECHNOLOGIES.


PROJECT STRUCTURE & SOURCE CODE

Front-end:
AbiSU.png, homepage.php, homepage.css, index.js

Back-end:
api.php, connect.php, history.php, logout.php, save_notice.php, settings.php

Excluded (Must be created locally):
sms_config.php (cointains api key, not included for security purposes), login.sql (database)


INSTALLATION & SETUP INSTRUCTION

Download the Source Code (Only the one's specified above)
Clone the repository or download the ZIP file containing all front-end and back-end source files to your local server directory (e.g., `htdocs` for XAMPP or `www` for WAMP):

```bash  `git clone [https://github.com/eizzielmariebacoy/abisu.git](https://github.com/eizzielmariebacoy/abisu.git)`


CONFIGURE SMS API (sms_config.php)

Create a separate PHP file named sms_config.php in the root directory and paste your generated SMS API key:

<?php
// sms_config.php
define('SMS_API_KEY', 'YOUR_GENERATED_API_KEY_HERE');
define('SMS_SENDER_NAME', 'YOUR_SMS_SENDER_NAME_HERE'); 


SET UP THE DATABASE

Create your own local MySQL database and import/create the required tables:
- Open phpMyAdmin (or your preferred MySQL client).
- Create a new database (check connect.php to verify the expected database name, username, and password).
- Create a login.sql file or run a query to set up the necessary tables for user authentication, weather advisories, and suspension notices.
- Ensure your database credentials in connect.php match your local server environment.


RUN THE APPLICATION

- Start your local Apache and MySQL services (via XAMPP, WAMP, or Laragon).
- Open your browser and navigate to http://localhost/abisu/homepage.php (or your configured local path).
