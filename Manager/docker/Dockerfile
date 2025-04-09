FROM ubuntu:20.04

LABEL maintainer="3129287226@qq.com"

ENV SOUR_FAST=./fastadmin.zip
ENV TMP_ZIP=/tmp/fastadmin.zip
ENV FAST_ROOT=/var/www/fastadmin
ENV FAST_WEB=$FAST_ROOT/public
ENV CONF_DIR=$FAST_ROOT/application/config.php
ENV ADDON_DIR=$FAST_ROOT/application/admin/controller/Addon.php
ENV UPLOAD_DIR=$FAST_ROOT/application/extra/upload.php
ENV SERVICE_DIR=$FAST_ROOT/vendor/karsonzhang/fastadmin-addons/src/addons/Service.php
ENV PHP_CONF=/etc/php/7.4/apache2/php.ini
ENV MYSQL_USER=root
ARG mysql_pass
ENV MYSQL_PASS=$mysql_pass
ENV APACHE_CONF=/etc/apache2/sites-available/000-default.conf
ENV SEDVAR="\/var\/www\/fastadmin\/public"
ENV DEBIAN_FRONTEND=noninteractive
ENV TIMER_DIR=./timer
ENV SQL_DIR=./data.sql


# 安装 tzdata 软件包，并设置时区为 Asia/Shanghai
RUN apt-get update && \
    apt-get install -y tzdata && \
    ln -fs /usr/share/zoneinfo/Asia/Shanghai /etc/localtime && \
    dpkg-reconfigure --frontend noninteractive tzdata && \
    apt-get clean

RUN apt-get update && apt-get install -y apache2 \
    php php-gd php-mysql libapache2-mod-php php-zip\
    mysql-server curl


COPY $SOUR_FAST /tmp/fastadmin.zip
COPY $TIMER_DIR /timer
COPY $SQL_DIR /data.sql

RUN mkdir ${FAST_ROOT} && \
    apt-get install -y unzip && \
    unzip ${TMP_ZIP} -d ${FAST_ROOT} && \
    rm -rf ${TMP_ZIP} && \
    chown -R www-data ${FAST_ROOT} && \
    chown -R www-data ${FAST_WEB} && \
    chown -R www-data ${FAST_ROOT}/application && \
    chown -R www-data ${FAST_ROOT}/runtime && \
    sed -i "s/DocumentRoot.*/DocumentRoot ${SEDVAR}/" ${APACHE_CONF} && \
    sed -i "s/app\.debug.*/app.debug', true) ,/" ${CONF_DIR} && \
    sed -i "s/unknownsource.*/unknownsource'           =>true,/" ${CONF_DIR} && \
    sed -i "/Please login and try to install/s/^/\/\//" ${ADDON_DIR} && \
    sed -i "/Service::valid/s/^/\/\//" ${SERVICE_DIR} && \
    sed -i "s/upload_max_filesize.*/upload_max_filesize = 15M/" ${PHP_CONF} && \
    sed -i "s/post_max_size.*/post_max_size = 15M/" ${PHP_CONF} && \
    sed -i "s/zlib.output_compression = Off/zlib.output_compression = on/" ${PHP_CONF} && \
    sed -i "s/;zlib.output_compression_level = -1/zlib.output_compression_level = 6/" ${PHP_CONF}

ADD start.sh /start.sh
ADD stop.sh /stop.sh
ADD init.sh /init.sh
RUN chmod 777 /start.sh && chmod 777 /stop.sh && chmod 777 /init.sh


EXPOSE 80






