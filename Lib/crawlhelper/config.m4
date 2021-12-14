PHP_ARG_ENABLE(crawlhelper, whether to enable evalhook support,
[  --enable-crawlhelper           Enable evalhook support])

if test "$PHP_EVALHOOK" != "no"; then
  PHP_NEW_EXTENSION(crawlhelper, crawlhelper.c, $ext_shared)
fi
