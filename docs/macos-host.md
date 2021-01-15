# Contribute to this plugin from a MacOS host

You can test the Linux behavior of this project using **Vagrant** (you have to install this on your host to continue).

```bash
vagrant up
vagrant ssh
sudo usermod -aG docker vagrant
sudo systemctl restart docker
```

You have to log out and log back for permission to be updated:
```bash
exit
vagrant shh
```

Enabled IPV6 support following [this guide](https://docs.docker.com/config/daemon/ipv6/). (Note that you'll have to create the file `/etc/docker/daemon.json`).

Add yarn: https://linuxize.com/post/how-to-install-yarn-on-ubuntu-18-04/

https://github.com/nodesource/distributions/blob/master/README.md#installation-instructions

Add deps to run playwright:
sudo apt-get install libnss3\
          libnspr4\
          libatk1.0-0\
          libatk-bridge2.0-0\
          libxcb1\
          libxkbcommon0\
          libx11-6\
          libxcomposite1\
          libxdamage1\
          libxext6\
          libxfixes3\
          libxrandr2\
          libgbm1\
          libgtk-3-0\
          libpango-1.0-0\
          libcairo2\
          libgdk-pixbuf2.0-0\
          libasound2\
          libatspi2.0-0


```bash
cd /vagrant
cp .env.example .env

# set DEBUG=0 in .env

SETUP_ONLY=1 ./run-tests.sh
```

sudo vim /etc/hosts

#172.16.0.50 wordpress5-6
fde4:8dba:82e1::c4 wordpress5-6
# select the one you want to try

Access with ipv4 : http://wordpress5-6/wp-admin

### Clean up environnement

To destroy the vagrant instance:

```bash
vagrant destroy
```