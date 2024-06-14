This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the [GNU General Public License](https://www.gnu.org/licenses/gpl-3.0.en.html) for more details.

# External Assignment for Moodle

This module creates an assignment in Moodle where the student's grades can be updated with the results of an assignment in an external system (e.g. GitHub Classroom). As well as the grade and feedback from the external system, there are separate fields for manual grading and feedback.

We have developed this module to integrate automatic grading from GitHub Classroom into Moodle.
The plugin is not limited to use with GitHub Classroom, it should work with any external system.
### Limitations
The plugin currently only supports individual assignments.
### Disclaimer
This plugin is being developed for my own classes and is still in testing. I am trying to make this plugin as safe and bug free as possible. I cannot give any guarantees or accept any liability if you use this plugin in your Moodle installation. I encourage you to study the source code (and give me feedback if you find any bugs) and install it on a test instance before using it.
## Installation and configuration
### Prerequisite
#### External user name
To assign grades to the correct user, the username in the external system (i.e. classroom, ...) must be set in the Moodle user profile. To add an additional field to your user profile, see https://docs.moodle.org/403/en/User_profile_fields.

This screenshot shows our setup:
![User profile custom field](https://it.bzz.ch/wikiV2/_media/howto/git/grading/classroom_moodle_userprofile.png)

## Installation and Configuration
### Prerequisite
#### External username
In order to assign grades to the correct user, the username in the external system (i.e. classroom, ...) must be set in the Moodle user profile. To add an additional field to your user profile, see https://docs.moodle.org/403/en/User_profile_fields.

This screenshot shows our setup:
![User profile custom field](https://it.bzz.ch/wikiV2/_media/howto/git/grading/classroom_moodle_userprofile.png)

### Installation
Download this plugin as a ZIP archive and install it into your Moodle *(see https://docs.moodle.org/403/en/Installing_plugins#Installing_a_plugin)*. During installation you will be asked to enter the nickname of the custom field for the external username you created above. In my setup this is "`github_username`".

### Web Service

Create a new external web service *(See https://docs.moodle.org/403/en/Using_web_services)* and add the function "`mod_externalassignment_update_grade`" to it. This will create an endpoint for the external system to send the grade and feedback. Note the token generated for this service.
#### Definition
- HTTP Method: `POST
- URL: `https://YOURMOODLE.HLQ/webservice/rest/server.php?wstoken=TOKEN&wsfunction=mod_externalassignment_update_grade`.
- Body:
    - assignment_name: String
    - user_name: String
    - Points: Float
    - max: Float
    - externallink: String
    - feedback: String

## Usage
These are the basic steps for using this module. The details depend on the type of mapping and the external system you are using.

### Setting up the mapping
1. Add the external username to your students' Moodle profiles.
2. Create an assignment in the external system.
3. Create a new external assignment in your Moodle with the following details
  - A link to the external assignment.
  - The name of the external assignment.
  - A description of the assignment.
  - the maximum number of points for external and manual grading.

### Grading
The external system needs a script that calls the web service in Moodle. Each time the web service is called it updates the external grade and feedback for the student.
You can also manually grade the assignment and provide feedback.


See the [Wiki](../../wiki) for more information.

