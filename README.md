### 🚧 For Development Environment
Run the following command for development environment.
```
composer install

```

To update dependency versions according to composer.json (Modifies your composer.lock)
```
composer update
```

### 🚀 For Production Environment
Run the following command for production environment to ignore the dev dependencies.
```
composer install --optimize-autoloader --no-dev -q

```

### 📦 For Build Release
Set execution permission to the script file by `chmod +x bin/build.sh` command. Now, Run the following bash script.
```
bin/build.sh
```