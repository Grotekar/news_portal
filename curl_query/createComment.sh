#!/bin/bash
curl -i -X POST -d "text=12345" -u '1:' http://localhost:8000/api/news/1/comments
