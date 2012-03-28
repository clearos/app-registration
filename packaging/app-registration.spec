
Name: app-registration
Epoch: 1
Version: 1.0.10
Release: 1%{dist}
Summary: System Registration
License: Proprietary
Group: ClearOS/Apps
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = 1:%{version}-%{release}
Requires: app-base

%description
System registration provides access to the Marketplace - a place where you will find the latest apps and software updates.  Creating an account and registering your system is quick and easy.

%package core
Summary: System Registration - APIs and install
License: LGPLv3
Group: ClearOS/Libraries
Requires: app-base-core
Requires: app-clearcenter

%description core
System registration provides access to the Marketplace - a place where you will find the latest apps and software updates.  Creating an account and registering your system is quick and easy.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/registration
cp -r * %{buildroot}/usr/clearos/apps/registration/

install -d -m 755 %{buildroot}/var/clearos/registration
install -D -m 0644 packaging/registration.conf %{buildroot}/etc/clearos/registration.conf

%post
logger -p local6.notice -t installer 'app-registration - installing'

%post core
logger -p local6.notice -t installer 'app-registration-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/registration/deploy/install ] && /usr/clearos/apps/registration/deploy/install
fi

[ -x /usr/clearos/apps/registration/deploy/upgrade ] && /usr/clearos/apps/registration/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-registration - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-registration-core - uninstalling'
    [ -x /usr/clearos/apps/registration/deploy/uninstall ] && /usr/clearos/apps/registration/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/registration/controllers
/usr/clearos/apps/registration/htdocs
/usr/clearos/apps/registration/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/registration/packaging
%exclude /usr/clearos/apps/registration/tests
%dir /usr/clearos/apps/registration
%dir /var/clearos/registration
/usr/clearos/apps/registration/deploy
/usr/clearos/apps/registration/language
/usr/clearos/apps/registration/libraries
%config(noreplace) /etc/clearos/registration.conf
