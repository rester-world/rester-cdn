FROM rester/rester-docker
MAINTAINER Kevin Park<kevinpark@webace.co.kr>

RUN mkdir /var/www/cfg

ADD cfg /var/www/cfg
ADD src /var/www/html
ADD nginx-conf/default.conf /etc/nginx/sites-available/default.conf
ADD nginx-conf/default-ssl.conf /etc/nginx/sites-available/default-ssl.conf

VOLUME ["/var/www/cfg"]
VOLUME ["/var/www/files"]

