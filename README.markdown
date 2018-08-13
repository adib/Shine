Shine is a web-based dashboard for indie Mac developers. It's designed to manage payment and order processing with PayPal and generate and email license files to your users using the [Aquatic Prime](http://www.aquaticmac.com/) framework. It even uploads each revision of your app into Amazon S3 and can produce reports from your users' demographic info (gathered via [Sparkle](http://sparkle.andymatuschak.org/)). It also serves as a central location to collect user feedback, bug reports, and support questions using the [OpenFeedback framework](http://github.com/tylerhall/OpenFeedback/tree/master).

This specific GitHub project is a complete rewrite of the previous version that was hosted on Google Code. Normally, I'm not an advocate of rewriting something that works, but in this case I felt it was needed. The original release (two years ago) was written in a very short period of time in a rush to release my first OS X application. This version uses an upgraded version of its PHP framework and is designed with future plans in mind.

Here's the [original blog post](http://clickontyler.com/blog/2009/08/shine-an-indie-mac-dashboard/) about the project if you're looking for a longer description.

Basic Usage
-----------
1. Unzip the installation folder into a non obvious directory on your web root directory.
2. Create a database, and import the mysql.sql file from the Shine folder.
4. Import the `changelog.sql` file from the Shine folder.
5. Create a user in the 'users' table.
6. Rename /includes/class.config.sample.php to /includes/class.config.php and modify to suit your server settings.
7  Rename `/aws-sdk-for-php/config-sample.inc.php` into `config.inc.php` and modify it to suit your server settings
8. Done, visit the webpage and login.

License
-------

This code is released under the MIT Open Source License. Feel free to do whatever you want with it.

Screenshots
-------
[![Screenshot 1](http://cdn.tyler.fm/blog/shine2-ss1-sm.png)](http://cdn.tyler.fm/blog/shine2-ss1.png)
[![Screenshot 2](http://cdn.tyler.fm/blog/shine2-ss2-sm.png)](http://cdn.tyler.fm/blog/shine2-ss2.png)
[![Screenshot 3](http://cdn.tyler.fm/blog/shine2-ss3-sm.png)](http://cdn.tyler.fm/blog/shine2-ss3.png)
[![Screenshot 4](http://cdn.tyler.fm/blog/shine2-ss4-sm.png)](http://cdn.tyler.fm/blog/shine2-ss4.png)
[![Screenshot 5](http://cdn.tyler.fm/blog/shine2-ss5-sm.png)](http://cdn.tyler.fm/blog/shine2-ss5.png)
