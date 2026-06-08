#!/bin/bash

# 1. DNS
echo "nameserver 8.8.8.8" > /etc/resolv.conf
echo "nameserver 1.1.1.1" >> /etc/resolv.conf

# 2. Почини репозитории
cat > /etc/yum.repos.d/CentOS-Base.repo << 'EOF'
[base]
name=CentOS-7 - Base
baseurl=http://vault.centos.org/7.9.2009/os/x86_64/
gpgcheck=1
gpgkey=file:///etc/pki/rpm-gpg/RPM-GPG-KEY-CentOS-7

[updates]
name=CentOS-7 - Updates
baseurl=http://vault.centos.org/7.9.2009/updates/x86_64/
gpgcheck=1
gpgkey=file:///etc/pki/rpm-gpg/RPM-GPG-KEY-CentOS-7

[extras]
name=CentOS-7 - Extras
baseurl=http://vault.centos.org/7.9.2009/extras/x86_64/
gpgcheck=1
gpgkey=file:///etc/pki/rpm-gpg/RPM-GPG-KEY-CentOS-7
EOF

# 3. Очисти кэш
yum clean all

# 4. Установи утилиты
yum install yum-utils -y

# 5. Добавь Tailscale репо
yum-config-manager --add-repo https://pkgs.tailscale.com/stable/centos/7/tailscale.repo

# 6. Установи Tailscale
yum install tailscale -y

# 7. Запусти
systemctl enable --now tailscaled

# 8. Авторизация
tailscale up
