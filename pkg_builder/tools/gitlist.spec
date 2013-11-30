Name: %{name}
Summary: %{summary}
Version: %{version}
Release: %{release}
URL: %{url}
License: %{license}
Group: %{group}
Vendor: %{vendor}
Packager: %{user}
Prefix: %{prefix}
BuildArch: noarch
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root
Source0: %{name}.tar

%description
%{summary}

%prep
%setup -c

%build

%install
%{__rm} -rf %{buildroot}
%{__mkdir} -p %{buildroot}%{prefix}
%{__cp} -Ra * %{buildroot}%{prefix}

%clean
rm -rf %{buildroot}

%files
%defattr(0755,root,root)
%{prefix}/*

%changelog
* Thu Jan 21 2013 Bruno Gurgel <bruno.gurgel@gmail.com>
- Initial
