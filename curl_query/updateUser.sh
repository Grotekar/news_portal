#!/bin/bash
curl -i -X PUT -u '14:' -d "firstname=Игорь&lastname=Petrenko&avatar=123456&is_admin=0" http://localhost:8000/api/users/14
