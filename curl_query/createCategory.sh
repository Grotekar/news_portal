#!/bin/bash
curl -i -X POST -d "name=cat&parent_category=0" -u '1:' http://localhost:8000/api/categories
