# Installation

I will describe a procedure using Ubuntu: I assume that users of other Linux distros can adapt the command lines below to suit their system and choose a specific PHP version above 8.1.

## Using multiple versions of PHP

It is recommended to add PPA (Personal Package Archives) "ondrej" for Ubuntu or "sury" for Debian (look at https://deb.sury.org/).

Here, we are using the "ondrej" one and we will install PHP8.3.

```bash
# Enter "root" mode (admin or sudoer):
sudo su
add-apt-repository ppa:ondrej/php
apt update
```

You can install all the PHP versions you want, but you'll have to choose which version NGINX (or APACHE) and CLI (Command Line Interface = Console) must use. We'll see it further.

```bash
# The default FragTale2 session handler stores user sesssion data in MongoDB, you'll need the PHP client.
# Of course, for your projects, you will surely install other PHP extensions such as php-curl, php-mb_string etc.
apt install php8.3 php8.3-cli php8.3-fpm php8.3-mongodb

# To install MongoDB server, see documentation: https://www.mongodb.com/docs/manual/tutorial/install-mongodb-on-ubuntu/

# Using relational database: it depends of your needs and which server you want to use
# MySQL
apt install php8.3-mysql
# Or Postgresql (but not supported yet by the ORM)
apt install php8.3-pgsql

# To install MySQL or MariaDB server, you will have to configure the server. Many documentations are available online.
apt install mysql-server
service mysql start

# Only for LOCAL (development) machines: Poedit. It is not required, but default translation system is **gettext**. Poedit is a simple tool that can open ".po" files to generate ".mo" files.
apt install poedit

# For gettext, you should read some documentation about this system.
# You will translate only hard-coded texts passed in the native PHP functions "_()", "gettext()" or "dgettext()".
# Translations stored in a database must be managed by a CMS or your own system (or eventually, by modules developped for this framework).

# Check your default locale
locale
# Check your list of installed locales
locale -a
# Reconfigure or install new locale, set your default locale
dpkg-reconfigure locales
```

PHP-FPM is a service (used by the web server: NGINX or APACHE). You'll have to start this service for PHP version you use and sometimes, you'll have to restart it if you have updated your translations in your source code (you have recompiled a ".mo" binary file, PHP-FPM must refresh the ".mo" file already loaded, but it doesn't matter for PHP-CLI).

```bash
service php8.3-fpm start
# If changes made in PHP configuration files, ".po" and ".mo" files
service php8.3-fpm restart
```

Select PHP version used in CLI mode.

```bash
update-alternatives --set php /usr/bin/php8.3
```

This means that when you'll execute a command using the PHP binary for CLI (/usr/bin/php), it will use /usr/bin/php8.3


## Clone the framework from GITHUB

```bash
# If not yet installed, install GIT
apt install git

# Obviously, you can "cd" at any location you want, depending your Apache2 or Nginx confs.
# But here, we will use the most common way to configure a web server.
cd /var/www
git clone git@github.com:FragBis/FragTale2.git
# It will create a new folder "FragTale2" in /var/www
# Of course, you can rename this folder.

cd FragTale2

# Permissions: for Debian-like, Apache and Nginx are user "www-data" that belongs to group "www-data".
# As you created /var/www/FragTale2 as "root" user, the owner of this folder is "root".
# Do NOT "chown" (change owner) this folder to "www-data:www-data".
# You'll have to let Apache or Nginx read and execute the files in the working folder. Only "root" user is allowed to write:
chmod -R 755 *
# Obviously, on development environment, you can do anything you want but you don't need to "chown" /var/www/FragTale2
# Your work will be placed in the "Project" folder (/var/www/FragTale2/Project will be created on setup).
# You will use your own versioned project in this Project folder.

# IMPORTANT! You DON'T NEED to version your custom project INCLUDING the FragTale2 source code.
# If you already have an existing project, clone it into Project folder
# (but of course, open your IDE with the framework to read all the source code).

## This framework is versioned and linked by default to master branch, so "git pull" will update your framework at the latest version
## If you encounter issues with newer version, then try to checkout at previous version (another branch) that worked with your project(s)
```

## Check that PHP option **short_open_tag** is enabled

**short_open_tag** allows you to open and close quickly a PHP tag into a template (a view), in the middle of HTML tags.

Basically, you can type:

```php
<?= "Any text" ?> instead of: <?php echo "Any text" ?>
```

You can use "nano" to edit "php.ini" file placed in your "8.X" folder, corresponding to your installed PHP version.

```bash
# To know your PHP version, type: php --version
# For our example, we are still with PHP8.3 and in root mode
nano /etc/php/8.3/fpm/php.ini
```

Then, press "Ctrl + W" and search for "short_open_tag"

```conf
short_open_tag = On
```

```bash
service php8.3-fpm restart
```

## Edit "/etc/hosts" to use a custom hostname [mycustomhostname.com] on a local machine

If you are deploying the application on your own machine for development you'll have to give some custom hostnames bound to IP 127.0.0.1, then edit file /etc/hosts

Hostname, project name and paths enclosed in [] are replaceable in my examples (and PHP version).

You will replace [mycustomhostname.com] by anything you want, of course without [].

```conf
127.0.0.1	localhost [mycustomhostname.com]
```
```bash
ping [mycustomhostname.com]
# Note for beginners: It will return "localhost 127.0.0.1" only on your local machine.
# In a network, other machines can access your server using the hostname if all machines are connected to the same DNS that will bind your IP address on the network to a given domain.
```


## Working with NGINX (recommended)

```bash
apt install nginx
```

Then, modify configuration file: (on Debian/Ubuntu, most commonly the default configuration file to modify is /etc/nginx/sites-available/default).

After installation, your system will try to start nginx. If Apache is already running, nginx will fail to start.

Just stop apache service (or use Apache, see next section "Using APACHE2"):

```bash
service apache2 stop
# And/or uninstall it
apt remove apache2
```

### Nginx configuration using only one (default) conf file

If your server hosts several domains and subdomains on the same framework (having one or more projects), the simplest way is to directly change the default settings, without adding any kind of "virtual host":

```conf
server {
	listen 80 default_server;
	listen [::]:80 default_server;

	# SSL configuration (you might install and use certbot to automatically generate a certification via letsencrypt on production server)
	#
	# listen 443 ssl default_server;
	# listen [::]:443 ssl default_server;
	# include snippets/snakeoil.conf
	 
	root [/var/www/FragTale2]/public;
	index index.php;
	server_name _;

	location / {
		# As mod rewrite
		rewrite ^(.*)$ /index.php?_route_index=$1 last;
		# You can alternatively use:
		# try_files $uri $uri/ /index.php?_route_index=$uri&$args;
	}

	# Pass PHP scripts to FastCGI server
	# Declare PHP version being used
	location ~ \.php$ {
		include snippets/fastcgi-php.conf;
		fastcgi_pass unix:/run/php/php8.3-fpm.sock;
	}

	# Deny access to .htaccess files, if Apache's document root concurs with nginx's one
	location ~ /\.ht {
		deny all;
	}
}
```

### Nginx configuration using Virtual Hosts

We have 2 sections in the same file (but you can create a second file in /etc/nginx/sites-available).

```conf
# Here, "root" folder (for Nginx) is /var/www/html by default
server {
	listen 80 default_server;
	listen [::]:80 default_server;

	# SSL configuration
	# (... if needed for HTTPS protocole)
	
	root /var/www/html;
	index index.php index.html index.htm index.nginx-debian.html;
	server_name _;
	location / {
		try_files $uri $uri/ =404;
	}
	location ~ \.php$ {
		include snippets/fastcgi-php.conf;
		fastcgi_pass unix:/run/php/php8.3-fpm.sock;
	}
	location ~ /\.ht {
		deny all;
	}
}

# Virtual Host configuration for "mycustomhostname.com"
#
# You can move this section into a different file under /etc/nginx/sites-available and symlink that into /etc/nginx/sites-enabled.
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

### Nginx permissions on custom locations

If you placed your project in a custom location such as "/home/[username]/www" where the owner is "[username]:[groupname]", you'll have to allow "www-data" to "cd" to this repository.

In Unix systems, a user belongs to at least one group and by default, it is most commonly the same name of the user, so it is frequently **username:username**.

By default, "www-data" is not allowed to access any directory except /var/www or specific directory like /tmp

Below, you will include the user "www-data" into your own group. Combined to the way you "chmod" your directory (for example "755"), this will allow "www-data" (the web server user on Ubuntu) to have at least read access on your folder.

```bash
# Add www-data to the user group (where the user group name is also the user name)
gpasswd -a www-data [username]

nginx -s reload
# Or start nginx if not yet
service nginx start
```

If you have some troubles with NGINX because of permission issues, the simplest way for beginners to deploy the application is to place it in /var/www or /var/www/html (the default root location seen in NGINX default configuration file at /etc/nginx/sites-available/default).

But in that case, and if you're on your local machine, you'll want to edit the files contained in your own project. Then, you'll have to be the owner of your project's folders (or at least, have write access).

**In summary**, you should let FragTale2 framework be owned by "root:root", denying write access to other users, but obviously, you will have to edit your own projects (in folder "Project") on development environments.

In any case, you'll have to let "www-data" write into "logs" directory (because the web server will write any logs and errors in this location).

Those kind of rules and permissions are crucials in production sites that should be handled by an administrator.


## Using APACHE2

```bash
apt install apache2
# Install PHP module for Apache corresponding to the PHP version you are using
apt install libapache2-mod-php8.3

# Ensure "mod_rewrite" is enabled
a2enmod rewrite
a2enmod deflate
service apache2 restart
# If using PHP-FPM
service php8.3-fpm restart
```

* Apache2 VirtualHost configuration file such as "/etc/apache2/sites-available/000.default.conf": by modifying this file, you will route **ALL** your domains by default to the "public" folder that becomes the only entry point accessible via HTTP. Of course, you can create another VirtualHost in another file in sites-available (and sites-enabled).

We just want to see which settings to change and which ones are needed.

```conf
DocumentRoot [/var/www/FragTale2]/public 
<Directory [/var/www/FragTale2]/public>
	AllowOverride All
	Require all granted
</Directory>
# "AllowOverride All" is needed to take account of the ".htaccess" file placed in "public" folder. Rewrite rules are defined in this file.
# Note for beginners: ".htaccess" is a hidden file that could not be displayed by default into your file explorer. All files having a name prepended by a dot are "hidden" files.
# On terminal, you can list all files in a directory using this command: "ls -la"
```

* Reload the Apache configuration:

```bash
# Still in root mode
service apache2 reload
```


# FragTale2 Console

The operating principle of the FragTale2 console is simply to execute a controller (a class which inherits from the "Controller" super class) via the "fragtale2" application file.

One controller = one class = one PHP file.

Mind that you can execute **ANY controller** with ./fragtale2


## Use the FragTale2 Console to setup your application the very first time

```bash
# You are still into folder [/var/www/FragTale2] and the file "fragtale2" must be EXECUTABLE. If not, type: chmod +x fragtale2
# Then, launch setup
./fragtale2 Console/Setup
# TIP 1: you can use "Tab" button to autocomplete sentences. Double hit "Tab" to list what you can type.
# TIP 2: "Ctrl + C" to kill process.
```

There are few properties to setup. Mainly, choose your locale (language). At this moment, FragTale Console supports french and english languages.

Setup will create required directories (Project, logs and Module) if they are not yet presents and the base configuration file needed for Console: **resources/configuration/application.json**

In development environment, you can set permissive access to "logs" directory. Some logs are written by "www-data" and others can be written when you use command lines as "root" (but the "root" can write anywhere in any case) or your own Linux user (with limited permissions).

* "logs" dir can be owned by "www-data:www-data": (following examples are advices, you can set permissions the way you prefer)

```bash
# Still in root mode
chown -R www-data:www-data logs
# If you want to execute FragTale2 Console with your regular Linux user, you can add "www-data" group to your user [username].
# Add "www-data" group to [username], as PRIMARY group
usermod -g www-data [username]
# Or add it as SECONDARY group
usermod -aG www-data [username]
# Then, allow users belonging to group "www-data" to write in it
chmod -R 775 logs
```

* Again in development environment, you should be the owner of "Project" directory:

```bash
chown -R [username]:[groupname] Project
# And allow this directory and its content to be readable by anyone (but it should already set to 755 by default):
chmod -R 755 Project
# Check it
ls -la
```

* The "Module" directory is reserved for the application's plugins. They could be installed via other Git projects or created by yourself. I could provide a list of modules in the future on my website, especially for a CMS module.


## Create your first project [MyCustomProject]

At this point, consider you are in development environment since you will create your own project. Type "exit" in console if you are still in root mode and stay on your regular Linux user.
Here, that's why you should be the owner of "Project" directory.

```bash
./fragtale2 Console/Project/Create
```

Project name will be prompted. Type [MyCustomProject] and confirm. This will automatically create a project from scratch in "Project" folder.

You'll have to check your new configuration file "[/var/www/FragTale2]/Project/[MyCustomProject]/resources/configuration/project.json"

**But first**, bind your domain [mycustomhostname.com] to your new project, by 2 ways:

Even if you have bound [mycustomhostname.com] to local IP "127.0.0.1", it is not enough for the FragTale2 application to route this domain to a specific project (because you can have multiple projects and multiple domains bound to 127.0.0.1).

* Then, you'll have to route [mycustomhostname.com] to your project [MyCustomProject].

```bash
# Here, we want to write "[/var/www/FragTale2]/resources/configuration/hosts.json" file. As it is placed in the application core, it should be written as root.
# so, we use "sudo" this time.
sudo ./fragtale2 Console/Setup/Hosts
```

At first prompt, type 1 to add new entry. Then, type your domain: [mycustomhostname.com]

And select your project to bind it to this domain. Confirm. Check "[/var/www/FragTale2]/resources/configuration/hosts.json" file.

Note that it would work the same to route "localhost" to a project.

Your site now should work for http://[mycustomhostname.com]


* You'll have to define if you are on "production" or on "development" environment. On your local machine, choose "development" to enable debug mode.

```bash
./fragtale2 Console/Project/Configure/Environment --project [MyCustomProject]
```
Choose the 2nd point. Then, you'll have to choose again among a list of already bound domain. By default, localhost should've already been listed and set to "development" environment.

For our example, we want to bind [mycustomhostname.com] to the "production" environment. Press enter without typing any number from the list of domains. This will prompt you to create a new entry. Then, select the domain [mycustomhostname.com] and finally, choose to bind it to the "production" environment (type the corresponding number) and press enter.

At this point, do not press "Ctrl+C" to kill application. Configuration has not been saved yet. Answer "no" while prompting to configure new host or another parameter. Console will send you a successfull message having saved the configuration file.

Check "[/var/www/FragTale2]/Project/[MyCustomProject]/resources/configuration/project.json" file. You can edit this file manually, but pay attention not to break the JSON format because it can crash your application. Usually, backup your configuration file.


## Database configuration

```bash
./fragtale2 Console/Project/Configure/Database --project [MyCustomProject]
```

Just follow dialogs step-by-step. This will configure your database connections in "[/var/www/FragTale2]/Project/[MyCustomProject]/resources/configuration/project.json"


# Conclusion

You can see that this framework **can handle multiple domains and multiple projects in the same base application, using multiple databases including MongoDB**.

You can deploy a website, a REST API and schedule cronjobs to execute rich PHP processes, using shared modules and classes between different projects.
