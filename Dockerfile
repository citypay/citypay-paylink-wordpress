FROM wordpress:latest
LABEL maintainer="Michael Martins <michael.martins@citypay.com>"

RUN apt-get update && apt-get install -y --no-install-recommends \
    unzip \
    less \
    vim \
    && rm -rf /var/lib/apt/lists/*

RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
	&& chmod +x wp-cli.phar \
	&& mv wp-cli.phar /usr/local/bin/wp \
	&& wp --info

ENV CITYPAY_PLUGIN_VERSION 1.3.0

COPY scripts/*.sh /usr/local/bin/

EXPOSE 80

WORKDIR /var/www/html
ENTRYPOINT ["citypay-entrypoint.sh"]