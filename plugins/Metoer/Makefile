.fake: all clean

TARGETS=meteor.min.js meteorupdater.min.js

all: $(TARGETS)

clean:
	rm -f $(TARGETS)

meteor.min.js: meteor.js
	yui-compressor $+ > $@

meteorupdater.min.js: meteorupdater.js
	yui-compressor $+ > $@
