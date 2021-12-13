### Trigger the POI vulnerability of drupal 7.78

1. Enter http://143.248.47.101:7272/drupal-7.78/
![img1](./drupal_1.png)

2. Login (Username: admin / Password: asdf1234)
![img2](./drupal_2.png)

3. Click Configuration -> File system, 
or enter http://143.248.47.101:7272/drupal-7.78/node?admin/config#overlay=admin/config/media/file-system
![img4](./drupal_3.png)

4. Enter `phar:///app/phar_validator/dummy_class_r353t.png` in `Temporary directory` field
![img5](./drupal_4.png)

5. Click `Save configuration`
