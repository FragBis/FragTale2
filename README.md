# FragTale2

```pre
@version FragTale 2.1 2024
@author	 Fabrice Dant fragtale.development@gmail.com
@license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
@license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
```

# FragTale 2.1 PHP Framework
 
This is the core of a server-side system coded in PHP.

Mind that all your work must be placed in *Project* folder.

Use Console to create your project and your controllers at the beginning.

## Presentation

This framework was coded all alone and at this moment, documentation has not been done yet.

Documentation will aim to explain how to use those tools and will be found on my website running this framework.

FragTale2 framework **can handle multiple domains and multiple projects in the same base application, using multiple databases including MongoDB**.

You can deploy a website, a REST API and schedule cronjobs to execute rich PHP processes, using shared modules and classes between different projects.

FragTale2 provides some useful **CLI tools** (database mapping, for example) and an **ORM**.

I also created an "extended design pattern" from Iterator that I named **DataCollection**, an iterable object that can be loaded with other iterables (like arrays), JSON files, MongoDB and relational database results. All handled in a same way. It's a major concept in this framework since the ORM works with it.

FragTale2 conception started in 2020, supporting PHP7.4 and PHP8.0.
But, as PHP8.1 introduced changes that are not compatible with previous way to implement certain native interfaces (for example, Iterator interface), this version of FragTale2 framework on GITHUB does not support anymore PHP8.0 and all previous PHP versions.

I created this PHP framework to match the way I see object oriented development. I don't think my framework "is better and easier" than Symfony, Zend or Phalcon. It just fit my needs.

## Requirements

* OS: Linux
* Web server: NGINX (recommended) or Apache2 (with "mod_rewrite")
* PHP8.1+
* Gettext (and eventually, POEdit) and "locales" you need (en_US.UTF-8, fr_FR.UTF-8)
* For MySQL, Postgresql or Oracle (or eventually MS SQL Server Express for Linux), PDO will do the job. You might have to install anyway the PHP client (for example "apt install php8.3-mysql"). For now, FragTale 2.1 **ORM** only supports MySQL.
* You might want to install XDebug with your favorite IDE (PHP Storm, Eclipse, Netbeans etc.): see last section below

## Quickest way to install

It performs on Linux Debian-like, but it can be adapted to other Linux distributions. We'll take PHP8.3 for these examples.

We'll see the installation approach on **Ubuntu**, in Command-Line Interface (CLI), using DEB package manager.

You can eventually execute following commands in a bash script in sudo, in a virtual machine or in a container,
assuming you want to run Nginx (and you should be able to comment or uncomment what you need):

```bash
#!/bin/bash

## Execute those commands in root or sudo:
# sudo su
## PHP repository for Ubuntu:
add-apt-repository ppa:ondrej/php
## PHP repository (Sury) for Debian: see https://gist.github.com/Razuuu/f646c3be44c5ba3b9c8e38b0a856d7b4

## To install MongoDB server, see: https://www.mongodb.com/docs/manual/tutorial/install-mongodb-on-ubuntu/
## Installing GIT, PHP, NGINX and MySQL servers
apt install git php8.3 php8.3-cli php8.3-fpm php8.3-mongodb php8.3-mysql nginx mysql-server

## Use PHP8.3 in CLI
update-alternatives --set php /usr/bin/php8.3

## Start services
service php8.3-fpm start
service nginx start

## You can change /var/www by any path you want
cd /var/www
git clone https://github.com/FragBis/FragTale2.git
cd FragTale2
# Stable branch
git checkout fragtale2.1

## Preparing to execute framework installation process.
## Declare some variables:
## Replace "mycustomhostname.com" and "MyCustomProject" by your host name and your project name
HOST_NAME="mycustomhostname.com"
PROJECT_NAME="MyCustomProject"
## Replace NGINX by APACHE if you want to use Apache2 anyway
WEB_SERVER_APP="NGINX"
NGINX_DEST_SITES_AVAILABLE="/etc/nginx/sites-available"
NGINX_DEST_SITES_ENABLE="/etc/nginx/sites-enabled"

## If you don't mind to answer prompts (recommended for first use), just type:
./fragtale2 Console/Install
## Or, to prevent most of prompts, pass these options:
# ./fragtale2 Console/Install --host $HOST_NAME --project $PROJECT_NAME --server $WEB_SERVER_APP --dest $NGINX_DEST_SITES_AVAILABLE --force

## Handle directory read/write access on "logs" and "Project" folders:
## You will need to let "www-data" own "logs" directory:
chown -R www-data:www-data logs
## "www-data" is the web server "Linux user" for NGINX and APACHE. It belongs to group "www-data" as well.
## In my opinion, you should add your Linux user to "www-data" (as primary) group and chmod 775 logs
## You will be able to write in logs dir during CRON processes ($SUDO_USER is the user that launched sudo):
usermod -g www-data $SUDO_USER
## Or if you don't want to belong to www-data as primary group, type:
# usermod -a -G www-data $SUDO_USER
## Then "chmod" allowing users from same group to write into logs directory:
chmod 775 logs

## Get your primary group:
USER_PRI_GP=`groups $SUDO_USER | awk '{print $3}'`
## You will have to own "Project" folder in DEVELOPMENT environment only
chown -R $SUDO_USER:$USER_PRI_GP Project
## You will have to protect all your configuration files the same way.

## For NGINX, create a symlink to enable "mycustomhostname.com" configuration file:
ln -s "${NGINX_DEST_SITES_AVAILABLE}/${HOST_NAME}" "${NGINX_DEST_SITES_ENABLE}/${HOST_NAME}"

service php8.3-fpm restart
service nginx restart
```

*IMPORTANT NOTE!*
If you experience a 404 accessing your site with your web browser, it might be caused by the fact that
Nginx (www-data) must have at least "read" access to *ALL THE WAY* to your "document root" of your website.
This often comes when you place your project in a custom location such as "/home/username/custom_folder".
*Each part of the path must be readable by www-data*

If installation succeeded, go to section **"General application setup"**


## Installation overview and more explanations

Still on **Ubuntu**.

*CLI:*

```bash
# Enter "root" mode (admin or sudoer):
sudo su
add-apt-repository ppa:ondrej/php
apt install php8.3 php8.3-cli php8.3-fpm

# To install MongoDB server, see: https://www.mongodb.com/docs/manual/tutorial/install-mongodb-on-ubuntu/
# Install the PHP client
apt install php8.3-mongodb

# Using relational database: MySQL
apt install php8.3-mysql

# To install MySQL or MariaDB server, you will have to configure the server. Many documentations are available online.
apt install mysql-server
service mysql start

# Check your default locale
locale
# Check your list of installed locales
locale -a
# Reconfigure or install new locale, set your default locale
dpkg-reconfigure locales

service php8.3-fpm start
# When changes have been made to the PHP configuration files and the ".mo" files: restart PHP-FPM
service php8.3-fpm restart

# Select PHP version used in CLI mode.
update-alternatives --set php /usr/bin/php8.3

# Clone the framework from GITHUB
# If not yet installed, install GIT
apt install git

# Here, we will use the most common way to configure a web server.
cd /var/www
git clone https://github.com/FragBis/FragTale2.git
# It will create a new folder "FragTale2" in /var/www
# Of course, you can rename this folder.

cd FragTale2
# Change to FragTale2.1 branch (stable)
git checkout fragtale2.1
```

Check that PHP option **short_open_tag** is enabled.

You can use *nano* to edit *php.ini* file placed in your "8.X" folder, corresponding to your installed PHP version.

*CLI:*

```bash
# To know your PHP version, type: php --version
# For our example, we are still with PHP8.3 and in root mode
nano /etc/php/8.3/fpm/php.ini
```

Then, press *"Ctrl + W"* and search for *short_open_tag*

*Configuration file: /etc/php/8.3/fpm/php.ini*

```conf
short_open_tag = On
```

*CLI:*

```bash
service php8.3-fpm restart
```

### On develoment environment only (not for production): edit */etc/hosts* to use a custom hostname [mycustomhostname.com]

Host name, project name and paths enclosed in [] (and PHP version) are replaceable in my examples.

You will replace [mycustomhostname.com] by anything you want, of course without [].

*System file: /etc/hosts*

```conf
127.0.0.1	localhost [mycustomhostname.com]
```

You can do this with this command (in root):

```bash
./fragtale2 Console/Setup/Etc/Hosts
```

Obviously, you'll have to choose between NGINX and APACHE. Although I recommend NGINX, keep APACHE if you have already deployed websites with it.


### Using NGINX (recommended)

*CLI:*

```bash
apt install nginx
# Create a base configuration file in "sites-available".
./fragtale2 Console/Setup/Etc/Nginx --host [mycustomhostname.com]
# Create a symlink to enable [mycustomhostname.com] configuration file
ln -s /etc/nginx/sites-available/[mycustomhostname.com] /etc/nginx/sites-enabled/[mycustomhostname.com]
# Then reload nginx conf
nginx -s reload
# Or start nginx if not done yet
service nginx start
service php8.3-fpm restart
```

*Configuration file created: /etc/nginx/sites-available/[mycustomhostname.com]*

```conf
# "Virtual Host" configuration for [mycustomhostname.com]
server {
	listen 80;
	listen [::]:80;
	server_name [mycustomhostname.com];
	root [/var/www/FragTale2]/public;
	index index.php;
	location / {
		rewrite ^(.*)$ /index.php?_route_index=$1 last;
	}
	location ~ \.php$ {
		include snippets/fastcgi-php.conf;
		fastcgi_pass unix:/run/php/php8.3-fpm.sock;
	}
	location ~ /\.ht {
		deny all;
	}
}
```


### Using APACHE2

```bash
# Install
apt install apache2 libapache2-mod-php8.3
# Enable required apache modules
a2enmod rewrite
a2enmod deflate
# Create configuration file
./fragtale2 Console/Setup/Etc/Apache --host [mycustomhostname.com]
# Enable new host conf
a2ensite [mycustomhostname.com]
# Start or restart services
service apache2 start
service php8.3-fpm restart
```

*Configuration file created: /etc/apache2/sites-available/[mycustomhostname.com].conf*


## General application setup

***Tips:**

1. You can use *Tab* button to autocomplete sentences
2. Double hit *Tab* to list what you can type
3. Press *"Ctrl + C"* to kill a process
4. Some controllers may have few CLI arguments that can be passed optionnally. Add *-h* argument to invoke help. For example: *./fragtale2 Console/Project/Controller/Create -h*


*CLI (still "root"):*

```bash
# The file "fragtale2" must be EXECUTABLE. If it's not, type:
chmod +x fragtale2
# Then, launch setup
./fragtale2 Console/Setup

# Follow instructions and dialogs

# Create your first project [MyCustomProject]
./fragtale2 Console/Project/Create

# Follow instructions and dialogs

# You'll have to route the domain [mycustomhostname.com] to your project [MyCustomProject]
./fragtale2 Console/Setup/Hosts

# Follow instructions and dialogs

```

Your site now should work for *http://[mycustomhostname.com]*

Check file *[/var/www/FragTale2]/Project/[MyCustomProject]/resources/configuration/project.json*.
You can edit this file manually, but pay attention not to break the JSON format because it can crash your application.
Usually, backup your configuration files.


## File permissions

File permissions in Linux is managed most of time by a combination of *chmod* and *chown*. I highly recommend familiarizing yourself with these commands.

Allow *www-data* (the web server user) to write in *logs* directory:

```bash
# You can give the ownership to "www-data" only on "logs" directory:
chown -R www-data:www-data [/var/www/FragTale2]/logs
chmod -R 644 [/var/www/FragTale2]/logs
```

**In development environment:**

As you have executed the commands above as root, you have created the folder of your project as root.
To be able to edit your files, give you the ownership of the *Project* folder:

```bash
chown -R [username]:[groupname] [/var/www/FragTale2]/Project
# Where [username] is you Linux user and [groupname] is one of the group you belong.
# On Ubuntu, by default your group name is also your user name.
# To know your groups, just type:
groups [username]

# From now on, there is no need to stay in root mode. Type:
exit
```

## Database configuration

*CLI (as regular Linux user):*

```bash
./fragtale2 Console/Project/Configure/Database --project [MyCustomProject]
# Follow instructions and dialogs
```

This will configure your database connections in *[/var/www/FragTale2]/Project/[MyCustomProject]/resources/configuration/project.json"*

Basically, you have by default 2 database connexions named *default_sql* and *default_mongo*. Set your own values for each parameter.  
You can add new database connexions that you will name by your own. This name is the **connector ID**.

## Environment configuration

You'll have to define if you are in *production* or in *development* environment when you are requesting [mycustomhostname.com] or any domain linked to this project. On your local machine, choose *development* to enable debug mode.

```bash
./fragtale2 Console/Project/Configure/Environment --project [MyCustomProject]
```  
It will ask you to choose between 2 actions. **You will have to execute those 2 actions**.


## Building models

A **Model** is a relational database mapping. It will create PHP classes upon existing database tables (there is no reverse engineering, i.e. no system to create tables upon PHP declarations, I do prefer create the database with SQL statements).

Those model classes allow you to use the ORM and query the database, using specific functions and returning results as DataCollection.

```bash
./fragtale2 Console/Project/Configure/Model --project [MyCustomProject]
```

Your models are placed in *[/var/www/FragTale2]/Project/[MyCustomProject]/Model/Sql*


## Creating new controllers

```bash
./fragtale2 Console/Project/Create/Controller --project [MyCustomProject]
```

There are 3 controller types (Web, Block and Cli):

1. **Web**: A web controller is accessible via HTTP request and routed via an URI that will be mentioned by Console after controller creation success.  

2. **Block**: All controllers can be used as blocks but you should not expose a block via HTTP request (like web controllers), so it is preferable to create a block controller as type *Block*.  

3. **Cli**: Mind that **any controller can be executed in console**, but you might want not to expose on the Web all controllers that are meant to be executed only in CLI, so create those controllers with *--type=Cli*  


Take a look at a controller created with the console to find out how it is built.

All controllers have basic functions executed in a specific order when the controller itself runs (*Controller::run*). The main functions are (in order of execution):  
* Controller::**__construct**
* Controller::**executeOnTop**
* Controller::**executeBeforeHttpRequestMethod** *(only for HTTP requests)*
* Controller::**executeOnHttpRequestMethodPost** *(only for HTTP requests during a "POST" submission)*
* Controller::**executeOnHttpRequestMethodGet** *(only for most common HTTP requests. Note that functions for methods "DELETE", "PUT", "PATCH" exist)*
* Controller::**executeAfterHttpRequestMethod** *(only for HTTP requests)*
* Controller::**executeOnConsole** *(only for CLI)*
* Controller::**executeOnBottom**

You'll have to place your code in the appropriate function and order. You'll need to think about how to inherit your functions when you extend a controller class.

**For example:**  
· you should start a session in *__construct*, in *executeOnTop* or in *executeBeforeHttpRequestMethod*.  
· if you want to start a session in a controller and its child controllers, don't overwrite *__construct* or the function where you placed the session start. Or use *parent::__construct* (or *parent::inheritedFunction*).  
· you must place your code in *executeOnHttpRequestMethodPost* to handle a form submission.


## About class namespace

The class autoloader system needs your class to be declared in a namespace that corresponds to the relative path of your PHP file.

**one PHP class = one PHP file**

If your PHP file is located in the folder */var/www/FragTale2/Project/MyCustomProject/Controller/Web/MyCustomFolder*  
Then, its namespace must be declared on top of your PHP file:

```php
<?php
// In: /var/www/FragTale2/Project/MyCustomProject/Controller/Web/MyCustomFolder/MyCustomController.php

namespace Project\MyCustomProject\Controller\Web\MyCustomFolder;

class MyCustomController {}
```

This makes it easier to autoload PHP classes and you won't have to include PHP files in your code using *include*, *require* or *require_once*. This also makes it easier to route your paths.


## About the routing system

This framework works with URL rewrite. All web controllers are auto-routed corresponding to their location in your file system, according a correct format.

For example, a controller having following relative path in your filesystem **Project/MyCustomProject/Controller/Web/MyNewController.php** is accessible via this URL: **http://mycustomhostname.com/my_new_controller**

Or **Project/MyCustomProject/Controller/Web/MyNewFolder/MyNewController.php** <=> **http://mycustomhostname.com/my_new_folder/my_new_controller**

**The controller name in camel case is called by an URL having no caps and adding underscores.** So, you should easily find the controller called by a HTTP request.

**Notes:**  
1. I'm working on a module that will store in MongoDB a list of routes (a mapping "URL/controller involved") that can also work with a CMS. But consider that the default, the automatic and the simplest routing behavior is the one described above.  
2. I don't provide a system out of the box that parse an URL like this: **http://mycustomhostname.com/example?id=1** to this **http://mycustomhostname.com/example/id/1** (like Symfony does)


## About template engine

This framework does not officially support template engines such as Twig. It simply uses *.phtml* files like Zend did. This file extension is easily supported by all PHP IDEs. It could have used *.php* files, but *.phtml* avoids confusions.

This framework can be used fully as a REST API and it aims performances.

FragTale2 framework buffers the HTML rendering (the *.phtml* files used as views) and sends the content at the very end of the process.

A **single general layout** (with a header, a footer, CSS and JS sources) can be used by all the web pages and it is placed in: *[/var/www/FragTale2]/Project/[MyCustomProject]/resources/templates/layouts*

Views correspond to controllers. Their HTML design are in *.phtml* files placed in: *[/var/www/FragTale2]/Project/[MyCustomProject]/resources/templates/views*

HTML views are inserted in the general layout only if *TemplateFormat* is *HTML*.

There are 7 Template Formats, but the main ones are *HTML*, *HTML_NO_LAYOUT* and *JSON*. In your controllers, you can explicit which *TemplateFormat* to use. Otherwise, it will use the default template format you have chosen during project creation.

*HTML_NO_LAYOUT* indicates that you want to send an HTML content in response to an AJAX call, without the general layout.

*JSON* indicates that you want to send JSON data. So, there is no HTML view to build. The framework will also send the appropriate headers to the client browser.


## About "media" files (JS, CSS, pictures, videos etc.) and the *Media* controller

It is highly recommended NOT to place your media files into *public* folder.
All media files must belong to a project, so they must be placed in: *[/var/www/FragTale2]/Project/[MyCustomProject]/resources/media*

For example, the FragTale2 system will automatically route the HTTP request: *http://[mycustomhostname.com]/media/img/picture.jpeg*  
To: *[/var/www/FragTale2]/Project/[MyCustomProject]/resources/media/img/picture.jpeg*

When you call the URL **http://[mycustomhostname.com]/media** you are routed to a specific controller **"Media"** which is automatically created when you create your project:  *[/var/www/FragTale2]/Project/[MyCustomProject]/Controller/Web/Media.php*

This *Media* controller is a PHP class (you can modify since it is in your project) that can handle custom rules or permissions to access certain files. You can also send images or documents stored in MongoDB, set headers with *"cache-control"* and define how long a file must be cached by the browser (etc.) or whatever you want that doesn't break the initial behavior of this controller.


## About the SessionHandler service used by FragTale2

This framework contains a specific SessionHandler service that uses MongoDB. If you don't want to store session data in MongoDB, just don't use this service to start a session (use native PHP function: *session_start()*).


## Using XDebug (example with Eclipse IDE, should work barely the same with PHP Storm)

Using XDebug at version **3.0.2** minimum.

Example below works with Eclipse IDE because it is a little bit less easy to configure than for PHP Storm.

Obviously, below are examples and you can use a later PHP version.

```bash
apt install php8.3-xdebug
phpenmod xdebug
nano /etc/php/8.3/mods-available/xdebug.ini
```

And check that the file contains:

```conf
zend_extension=xdebug.so
xdebug.mode=debug
xdebug.client_host=127.0.0.1
xdebug.client_port=9003
```

Restart or reload Apache or Nginx, and PHP-FPM.

In Eclipse IDE, go to "Window" > "Preferences" > "PHP" > "Installed PHPs"

Select or add your PHP version (8.1+) and edit its configuration. On "Debugger" tab, check if Xdebug is selected and specify the port (9003).

Now, go to "Window" > "Preferences" > "PHP" > "Servers" and click "New" to add a new server.

On "Server" tab, type:

- "Server name": [mycustomhostname.com]
- "Server properties / Base URL": http://[mycustomhostname.com]
- "Server properties / Document Root": [/var/www/FragTale2]/public

On "Debugger" tab: just check if Xdebug is selected and port is 9003

In your Web browser (Firefox or Chromium), install extension "xdebug helper". You should have now a "bug" icon near your address bar that you have to activate (icon becomes green) if you are browsing for example "http://[mycustomhostname.com]".

That's it, your IDE should launch automatically Xdebug and break at defined points you choose in your code.

To debug a script in a console (using for example "./fragtale2 Project/[MyCustomProject]/Controller/Cli/[MyScript]), you might need to export an environment variable:

```bash
export XDEBUG_SESSION=1;
./fragtale2 Project/[MyProject]/Controller/Cli/[MyScript]
```

Your Eclipse IDE should now allow you to debug your code in a terminal that is really usefull for Unit Tests or for example to break the process on code placed in exception caught (you need to define your breakpoints).

You can also benchmark your controllers if you activate debug mode in your project configuration file (in "environments" section).
