#!/bin/bash
curl -i -X PUT -d "firstname=Игорь&lastname=Petrenko&avatar=123456&is_admin=1" http://localhost:8000/api/users/1
