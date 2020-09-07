#!/bin/bash
curl -i -X POST -d "user_id=5&description=12345" -u '1:' http://localhost:8000/api/authors
