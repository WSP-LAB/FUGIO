### Trigger the POI vulnerability of typo3

1. Enter http://143.248.47.101:7272/typo3_9.3.0/typo3/
![img1](./typo3_1.png)

2. Login (Username: admin / Password: asdf1234)
![img2](./typo3_2.png)

3. Click `Page` -> `New TYPO3 site` (right click) -> `New`
![img3](./typo3_3.png)

4. Enter any contents in `Page Title` field and click `Save`
![img4](./typo3_4.png)

5. Right click the generated page and click `Content` button
![img5](./typo3_5.png)

6. Click `Header Only` button
![img6](./typo3_6.png)

7. Enter `phar:///app/phar_validator/dummy_class_r353t.png` in `Link` field
![img7](./typo3_7.png)

8. Click `Save` button
