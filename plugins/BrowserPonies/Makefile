# Update version after release
VERSION=0.4
RELEASECOLOR1=\#B1EDB9
RELEASECOLOR2=\#4CC44F
RELEASECOLOR3=\#70CB71

all: ponycfg release minify

quick: ponycfg release

minify:
	yui-compressor browserponies.js > browserponies.min.js ;

ponycfg:
	echo '/%%ponyinilist%%/ c\\' > newponycfg.sed ; \
	./convert.py ponies/*/*.ini > newponycfg.json ; \
	sed -e 's/\\/\\\\/g' newponycfg.json >> newponycfg.sed ; \
	sed -f newponycfg.sed .ponycfg.tmp.js > ponycfg.js ; \
	rm -f newponycfg.* ;

release:
	sed -e 's/%%releasecolor1%%/$(RELEASECOLOR1)/g' \
		-e 's/%%releasecolor2%%/$(RELEASECOLOR2)/g' \
		-e 's/%%releasecolor3%%/$(RELEASECOLOR3)/g' \
		.gui.tmp.css > gui.css ;
