#!/bin/bash
curl -i -X PUT -u '1:' -d "name=cate&parent_category=0" http://localhost:8000/api/categories/2
