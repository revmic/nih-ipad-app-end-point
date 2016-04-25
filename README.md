# nih-ipad-app-end-point
An end-point for centrally storing data from the NIH iPad app

The problem of the last mile for a large multi-site study can be solved by
introducing efficient and automatic methods for the centralized collection
of data. This web-based server software receives and stores data from a
large number of devices in a secure way.

## Data collection instrument

The NIH Toolbox application (http://www.nihtoolbox.org) provides a large number of assessment instruments that can be captured on an iPAD. This project provides a simple backend that allows users to capture results from multiple iPADs at a central location.

## Setup on the server

The server based code consists of a single php script. Make sure that your web-server supports php. The script can be set up in the following way to support multiple sites (siteA, siteB, siteC) collecting instrument with multiple iPad's for each site.

```
/var/www/html/
├── d
│   ├── siteA
│   │   └── r.php -> ../../receiver.php
│   ├── siteB
│   │   └── r.php -> ../../receiver.php
│   └── siteC
│       └── r.php -> ../../receiver.php
├── README.md
├── .htaccess
└── receiver.php
```

One way to secure the data comming in from each site is to use basic authentication. Create an .htaccess file in the root of your project (here /var/www/html) with the following content:

```
AuthType Basic
AuthName "Restricted Files"
AuthBasicProvider file
AuthUserFile /var/www/passwords
Require user admin
```

The above .htaccess file points to a passwords file (in a secure location) that contains a list of site user names and passwords. This file can be created using the htpasswd tool which is part of apache2:
```
htpasswd -c /var/www/passwords siteUserA
```

In order to provide groups of users access to each individual site account the apache configuration file can use user groups. Here an example:
```
<Directory /var/www/html/applications/ipad-app/d/siteA>
    AuthType Basic
    AuthName intranet
    AuthUserFile /var/www/passwords
    AuthGroupFile /var/www/groups
    Require group siteA
    Order allow,deny
    Satisfy any
</Directory>				      
```

The /var/www/groups file lists group names (such as siteA) and user names. This way a single user can also have access to more than one site. Here an example on the content of /var/www/groups:
```
siteA: siteUserA
```

The above settings should allow you to configure a central data repository for your single or multi-site project.

## Setup on the client

The iPAD app has a field to enter the URL for your central data collection site together with the user name (siteUserA) and the password of that user. If the test connection works (test) you can upload the data to the server. All uploaded datasets appear inside the site directories /var/www/html/d/siteA/ and are labeled with the date and time of the upload. The content of each file is the collected data as a comma-separated table of values.

## Technical Notes

In order to test the server one can emulate the actions that the NIH Toolbox iPad application performs.

### Login
```
curl -F "action=test" https://<your server>/d/siteA.php
```
Results in: Error HTML "Unauthorized"

```
curl --user <user name> -F "action=test" https://<your server>/d/siteA/r.php
```
Will asks for password for the given user, responds with  { "message": "ok" }

### Store files
```
curl --user <user name> -F "action=store" https://<your server>/d/sA/r.php
```
Result: Error json message: {"message":"Error: no files attached to upload"}

```
echo "1,2,3,4" > test.csv
curl --user <user name> -F "action=store" -F "upload=@test.csv" https://<your server>/d/sA/r.php
```
Result: A single file is stored, json object with error=0 returned

``` 
echo "1,2,3,4,5" > test2.csv
curl --user <user name> -F "action=store" -F "upload[]=@test.csv" -F "upload[]=@test2.csv" https://abcd-report.ucsd.edu/applications/ipad-app/d/sA/r.php
```
Result: Two files are stored on the server, json object with error=0 returned


