FROM php:7.2-stretch

ARG GPG_KEY=3F7A1D16FA4217B1DC75E1C9FFE35B7F15DFA1BA
ARG SHORT_DISTRO_NAME=zookeeper-3.5.5
ARG DISTRO_NAME=apache-zookeeper-3.5.5-bin

COPY test/docker-entrypoint.sh /

RUN set -eux; \
    mkdir -p /usr/share/man/man1 /plugin /zk/datalog /zk/data /zk/conf /zk/logs; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        openjdk-8-jre-headless \
        libzookeeper-mt-dev \
        ca-certificates \
        dirmngr \
        gnupg \
        netcat \
        wget; \
    export GNUPGHOME="$(mktemp -d)"; \
    gpg --keyserver ha.pool.sks-keyservers.net --recv-key "$GPG_KEY" || \
    gpg --keyserver pgp.mit.edu --recv-keys "$GPG_KEY" || \
    gpg --keyserver keyserver.pgp.com --recv-keys "$GPG_KEY"; \
    pecl install zookeeper; \
    yes | pecl install xdebug; \
    echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini; \
    docker-php-ext-enable zookeeper; \
    groupadd -r zookeeper --gid=1011; \
    useradd -r -g zookeeper --uid=1011 zookeeper; \
    chown -R zookeeper:zookeeper /zk/; \
    wget -q "https://www.apache.org/dist/zookeeper/$SHORT_DISTRO_NAME/$DISTRO_NAME.tar.gz"; \
    wget -q "https://www.apache.org/dist/zookeeper/$SHORT_DISTRO_NAME/$DISTRO_NAME.tar.gz.asc"; \
    gpg --batch --verify "$DISTRO_NAME.tar.gz.asc" "$DISTRO_NAME.tar.gz"; \
    tar -zxf "$DISTRO_NAME.tar.gz"; \
    mv "$DISTRO_NAME/conf/"* "/zk/conf"; \
    rm -rf "$GNUPGHOME" "$DISTRO_NAME.tar.gz" "$DISTRO_NAME.tar.gz.asc"; \
    echo 'dataDir=/zk/data' > /zk/conf/zoo.cfg; \
    echo 'dataLogDir=/zk/datalog' >> /zk/conf/zoo.cfg; \
    echo 'tickTime=2000' >> /zk/conf/zoo.cfg; \
    echo 'initLimit=5' >> /zk/conf/zoo.cfg; \
    echo 'syncLimit=2' >> /zk/conf/zoo.cfg; \
    echo 'autopurge.snapRetainCount=3' >> /zk/conf/zoo.cfg; \
    echo 'autopurge.purgeInterval=0' >> /zk/conf/zoo.cfg; \
    echo 'maxClientCnxns=60' >> /zk/conf/zoo.cfg; \
    echo 'standaloneEnabled=true' >> /zk/conf/zoo.cfg; \
    echo 'admin.enableServer=false' >> /zk/conf/zoo.cfg; \
    echo 'clientPort=2181' >> /zk/conf/zoo.cfg; \
    chown -R zookeeper:zookeeper "/zk" "/plugin" "/$DISTRO_NAME"; \
    chmod +x docker-entrypoint.sh; \
    apt-get purge -y wget gnupg; \
    rm -rf /var/lib/apt/lists/*

ENV PATH=$PATH:/$DISTRO_NAME/bin \
    ZOOCFGDIR=/zk/conf \
    ZOO_LOG_DIR=/zk/logs

USER zookeeper

WORKDIR "/plugin"

VOLUME ["/plugin"]

ENTRYPOINT ["/docker-entrypoint.sh"]
