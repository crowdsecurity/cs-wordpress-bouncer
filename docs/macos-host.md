# Contribute to this plugin from a MacOS host

You can test the Linux behavior of this project using **Vagrant**.

## One time setup

### Run the VM and initialize it

```bash
vagrant up
vagrant ssh
sudo usermod -aG docker vagrant
sudo systemctl restart docker
```

You have to log out and log back for permission to be updated:

```bash
exit
vagrant ssh
```
### Enabled Docker IPV6 support inside the Linux VM

follow [this guide](https://docs.docker.com/config/daemon/ipv6/). (Note that you'll have to create the file `/etc/docker/daemon.json`).


### Install NodeJS

Follow [this guide](https://github.com/nodesource/distributions/blob/master/README.md#installation-instructions)
### Install Yarn

Follow [this guide](https://linuxize.com/post/how-to-install-yarn-on-ubuntu-18-04/).

### Add deps to run playwright

```bash
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
```

### Edit you local host file:

Type `sudo vim /etc/hosts` and add:

```bash
# select the one you want to try by uncommenting only ont of the two
# 172.16.0.50 wordpress5-6 # Uncomment to use IPV4
fde4:8dba:82e1::c4 wordpress5-6 # Uncomment to use IPV6
```

### Configure your `.env` file

```bash
cd /vagrant
cp .env.example .env
```

Update the created `.env` file with:

```bash
DEBUG=0
DOCKER_HOST_IP=172.16.238.1 # OR IF YOU WANT TO TEST WITH IPV6 USE: 2001:3200:3200::1
```

### Run "plugin auto setup" via e2e tests (limited to setup steps)

```bash
SETUP_ONLY=1 ./run-tests.sh
```

### Browse the WordPress website admin

Visit [http://wordpress5-6/wp-admin](http://wordpress5-6/wp-admin).

### Run tests

```bash
SETUP_ONLY=1 ./run-tests.sh
```

### Stop the Virtal Machine
```bash
vagrant stop # vagrant up to start after
```

### Clean up environnement

To destroy the vagrant instance:

```bash
vagrant destroy
```