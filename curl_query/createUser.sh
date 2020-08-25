#!/bin/bash
curl -i -X POST -d "firstname=Nicholaus&lastname=Lehner&avatar=https://s3.amazonaws.com/uifaces/faces/twitter/dmackerman/128.jpg&is_admin=0" http://localhost:8000/api/users
