vendor/phpdocumentor/phpdocumentor/bin/phpdoc run -d src -t web/doc --template="templates/zend"

rm web/doc/files/*Resources*.html

wkhtmltopdf --margin-bottom 30mm --margin-left 20mm --margin-right 20mm --margin-top 30mm $(find web/doc/files/*.html) phpDoc.pdf 

#  -B, --margin-bottom <unitreal>      Set the page bottom margin
#  -L, --margin-left <unitreal>        Set the page left margin (default 10mm)
#  -R, --margin-right <unitreal>       Set the page right margin (default 10mm)
#  -T, --margin-top <unitreal>         Set the page top margin
