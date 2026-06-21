# php-src Docker Development Environment

This is a lightweight Docker environment for building php-src from a Git
checkout. It provides the generator and build tools needed for parser/scanner
work: Autoconf, Bison, re2c, a compiler toolchain, libxml2, SQLite, ccache, gdb,
and valgrind.

## Build the Image

From the repository root:

```sh
PHP_SRC_DEV_UID=$(id -u) PHP_SRC_DEV_GID=$(id -g) \
  docker compose -f docker/dev/compose.yml build
```

The UID/GID variables are most useful on Linux hosts so generated files are not
owned by root. They are optional on Docker Desktop for macOS.

## Open a Shell

```sh
PHP_SRC_DEV_UID=$(id -u) PHP_SRC_DEV_GID=$(id -g) \
  docker compose -f docker/dev/compose.yml run --rm php-src-dev
```

## Configure and Build php-src

Inside the container:

```sh
./buildconf --force
./configure --enable-debug --enable-zts --enable-opcache
make -j"$(nproc)"
sapi/cli/php -v
```

## Run Targeted Tests

Inside the container after a successful build:

```sh
TEST_PHP_ARGS="-q" make test TESTS="Zend/tests/access_modifiers"
```

For OPcache CLI behavior:

```sh
TEST_PHP_ARGS="-q -d opcache.enable_cli=1" \
  make test TESTS="Zend/tests/access_modifiers"
```

## Generated Files

Parser and scanner work may regenerate files under `Zend/` through
`scripts/dev/genfiles` or `buildconf`. Review generated diffs carefully before
committing.

