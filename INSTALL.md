# GitList Installation
* Download GitList from [gitlist.org](http://gitlist.org/) and decompress to your `/var/www/gitlist` folder, or anywhere else you want to place GitList.
* Rename the `config.ini-example` file to `config.ini`.
* Open up the `config.ini` and configure your installation. You'll have to provide where your repositories are located and the base GitList URL (in our case, http://localhost/gitlist).
* Create the cache folder and give read/write permissions to your web server user:

```
cd /var/www/gitlist
mkdir cache
chmod 777 cache
```

That's it, installation complete!

## Webserver configuration
Apache is the "default" webserver for GitList. You will find the configuration inside the `.htaccess` file. However, nginx and lighttpd are also supported.

### nginx server.conf

```
server {
    server_name MYSERVER;
    access_log /var/log/nginx/MYSERVER.access_log main;
    error_log /var/log/nginx/MYSERVER.error_log debug_http;

    root /var/www/DIR;
    index index.php;

#   auth_basic "Restricted";
#   auth_basic_user_file rhtpasswd;

    location = /robots.txt {
        allow all;
        log_not_found off;
        access_log off;
    }

    location ~* ^/index.php.*$ {
       fastcgi_pass 127.0.0.1:9000;
       include fastcgi.conf;
    }

    location / {
        try_files $uri @gitlist;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico)$ {
    add_header Vary "Accept-Encoding";
        expires max;
        try_files $uri @gitlist;
        tcp_nodelay off;
        tcp_nopush on;
    }

#   location ~* \.(git|svn|patch|htaccess|log|route|plist|inc|json|pl|po|sh|ini|sample|kdev4)$ {
#       deny all;
#   }

    location @gitlist {
        rewrite ^/.*$ /index.php;
    }
}
```

### lighttpd

```
# GitList is located in /var/www/gitlist
server.document-root        = "/var/www"

url.rewrite-once = (
    "^/gitlist/web/.+" => "$0",
    "^/gitlist/favicon\.ico$" => "$0",
    "^/gitlist(/[^\?]*)(\?.*)?" => "/gitlist/index.php$1$2"
)
```

### hiawatha

```
UrlToolkit {
    ToolkitID = gitlist
    RequestURI isfile Return
    # If you have example.com/gitlist/ ; Otherwise remove "/gitlist" below
    Match ^/gitlist/.* Rewrite /gitlist/index.php
    Match ^/gitlist/.*\.ini DenyAccess
}
```
