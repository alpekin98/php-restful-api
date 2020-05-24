'#!/bin/sh'
echo "Guncelleme islemi basladi!"
sudo git stash
sudo git pull origin master
sudo git stash pop
echo "Islem tamamlandi!"