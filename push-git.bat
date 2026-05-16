@echo off

set /p commitmsg=Masukkan pesan commit: 

git status
git add .
git commit -m "%commitmsg%"
git push origin main
git status

pause