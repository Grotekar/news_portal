#!/bin/bash
curl -i -X POST -d "firstname=Игорь&lastname=Lehner&avatar=https://s3.amazonaws.com/uifaces/faces/twitter/dmackerman/128.jpg" http://localhost:8000/api/users
