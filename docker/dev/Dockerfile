# syntax=docker/dockerfile:1

FROM debian:bookworm-slim

ARG DEBIAN_FRONTEND=noninteractive
ARG USER_ID=1000
ARG GROUP_ID=1000

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        autoconf \
        bison \
        build-essential \
        ca-certificates \
        ccache \
        curl \
        file \
        gdb \
        git \
        less \
        libsqlite3-dev \
        libxml2-dev \
        make \
        pkg-config \
        procps \
        re2c \
        sudo \
        valgrind \
        vim-tiny; \
    rm -rf /var/lib/apt/lists/*

RUN set -eux; \
    if ! getent group "${GROUP_ID}" >/dev/null; then \
        groupadd --gid "${GROUP_ID}" php; \
    fi; \
    useradd --uid "${USER_ID}" --gid "${GROUP_ID}" --create-home --shell /bin/bash php; \
    mkdir -p /home/php/.cache/ccache; \
    chown -R "${USER_ID}:${GROUP_ID}" /home/php; \
    echo 'php ALL=(ALL) NOPASSWD:ALL' > /etc/sudoers.d/php; \
    chmod 0440 /etc/sudoers.d/php

USER php
WORKDIR /workspaces/php-src

ENV CCACHE_DIR=/home/php/.cache/ccache
ENV PATH=/usr/lib/ccache:$PATH

CMD ["/bin/bash"]

