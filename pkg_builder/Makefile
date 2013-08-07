# Main Info
NAME = gitlist
DESCRIPTION = "An elegant and modern git repository viewer"
LICENSE = New BSD
GROUP = gitlist
VENDOR = gitlist.org
URL = "http://www.gitlist.org"

#BUILD Info
PREFIX = /usr/share
PROJROOT = "$(shell pwd)"
SRCROOT = "$(shell pwd)/gitlist"
UPSTREAM_VERSION = $(shell cat tools/release.info | head -n1 | cut -d"=" -f2)
BUILD_STAMP = $(shell date +"%Y%m%d%H%M%S")

#Packager Info
PACKAGER = $(shell git config user.name)
PACKAGER_MAIL = $(shell git config user.email)

#Debian Package Info
PACKAGE-VERSION= 1
DEBIAN_BUILD_ROOT = ${PROJROOT}/debian/
PROJECT_DEBIAN_LIKE_NAME=$(shell cat tools/release.info | grep name | cut -d"=" -f2)
DEBIAN_NAME=$(PROJECT_DEBIAN_LIKE_NAME)$(shell echo "_")$(UPSTREAM_VERSION)-${PACKAGE-VERSION}$(shell echo "_all.deb")
DEBIAN_VERSION = 
# Generating control file
define control
Package: $(PROJECT_DEBIAN_LIKE_NAME)
Version: $(UPSTREAM_VERSION)-${PACKAGE-VERSION}
Architecture: all
Section: web
Priority: optional
Maintainer: "${PACKAGER} <${PACKAGER_MAIL}>"
Description: ${DESCRIPTION}
endef
export control

all:
	@echo "... $(UPSTREAM_VERSION)"
	@echo "... $(PACKAGER)"
	@echo "... $(PACKAGER_MAIL)"
	@echo "... $(DEBIAN_NAME)"

help: 
	@echo "To use this make file just:"
	@echo "Download the gitlist tarball and stract it into a folder called gitlist"
	@echo "make [build_deb|build_rpm|build(apache|nginx|lighthttp)]"

clean_deb: 
	@echo "Cleaning . . ."
	@rm -rf ${DEBIAN_BUILD_ROOT}/*.deb
	@rm -rf ${PROJROOT}/debian

prepare_deb: clean_deb
	@echo "############################### - Building DEB"
	@mkdir ${DEBIAN_BUILD_ROOT} -pv
	@mkdir ${DEBIAN_BUILD_ROOT}/DEBIAN -pv
	@mkdir ${DEBIAN_BUILD_ROOT}${PREFIX}/${PROJECT_DEBIAN_LIKE_NAME} -pv

copy_deb_files: prepare_deb
	@echo "$$control" > ${DEBIAN_BUILD_ROOT}/DEBIAN/control

copy_deb: copy_deb_files
	@echo Sync files
	@rsync -avz ${SRCROOT} ${DEBIAN_BUILD_ROOT}${PREFIX}/

md5sum_deb: copy_deb
	@cd debian; find . -type f ! -regex '.*\.hg.*' ! -regex '.*?debian-binary.*' ! -regex '.*?DEBIAN.*' | xargs -d "\n" md5sum > DEBIAN/md5sums

deb_uniq: md5sum_deb
	@mkdir ${PROJROOT}/pkg -p
	@dpkg -b debian $(DEBIAN_NAME);
	@mv $(DEBIAN_NAME) ${PROJROOT}/pkg/
	@rm debian -rf
	@echo '### Wrote $(DEBIAN_NAME) in ${PROJROOT}/pkg/ . . . . . Success'


build_deb: deb_uniq

#### RPM STUFF

RPM_NAME=$(PROJECT_DEBIAN_LIKE_NAME)$(shell echo "_")$(UPSTREAM_VERSION)-${PACKAGE-VERSION}$(shell echo "_all.rpm")

DIST_DIR = dist
TAR_DIR = tar

RPM_DIR = rpm
RPM_DIRS = SPECS RPMS SOURCES BUILD

clean_rpm:
	@echo Cleaning temporary dirs...
	@rm -rf $(TAR_DIR)
	@rm -rf $(RPM_DIR)
	@rm -rf $(DIST_DIR)

rpm_init:   clean_rpm
	@echo Creating directories...
	@echo $(DIST_DIR)
	@mkdir -p $(DIST_DIR)
	@for dir in $(RPM_DIRS); do \
                echo $(RPM_DIR)/$$dir; \
                mkdir -p $(RPM_DIR)/$$dir; \
        done

rpm_preptar:    rpm_init
	@echo Copying files to generate tar...
	@echo creating directory: $(TAR_DIR)/
	@mkdir $(TAR_DIR)/ -p
	@rsync -avz --exclude ".git" --exclude ".gitignore" --exclude "builder" gitlist $(TAR_DIR)/

rpm_tar:    rpm_preptar
	@echo Generating tarball...
	@cd $(PROJROOT)/$(TAR_DIR); \
                tar cf $(PROJROOT)/$(RPM_DIR)/SOURCES/$(NAME).tar .

rpm:    rpm_tar
	@echo Calling rpmbuild...
	@echo Vesion: $(VERSION)
	@cp tools/$(NAME).spec $(RPM_DIR)/SPECS/
	
	@cd $(PROJROOT)/$(RPM_DIR)/SPECS ; \
                rpmbuild -bb \
                        --buildroot="$(PROJROOT)/$(RPM_DIR)/BUILD/$(NAME)" \
                        --define "_topdir $(PROJROOT)/$(RPM_DIR)" \
                        --define "name $(NAME)" \
                        --define "summary "$(DESCRIPTION)"" \
                        --define "version $(UPSTREAM_VERSION)" \
                        --define "release $(PACKAGE-VERSION)" \
                        --define "url _$(URL)_" \
                        --define "license $(LICENSE)" \
                        --define "group $(GROUP)" \
                        --define "vendor $(VENDOR)" \
                        --define "packager $(PACKAGER)" \
                        --define "prefix $(PREFIX)" \
                        --define "source_dir $(PROJROOT)/$(RPM_DIR)/SOURCES" \
                        $(NAME).spec
	@echo Copying generated RPM to dist dir...
	@mkdir ${PROJROOT}/pkg -p
	@cp $(PROJROOT)/$(RPM_DIR)/RPMS/noarch/*.rpm $(PROJROOT)/pkg
	@rm -rf $(TAR_DIR)
	@rm -rf $(RPM_DIR)
	@rm -rf $(DIST_DIR)

		

build_rpm: rpm

