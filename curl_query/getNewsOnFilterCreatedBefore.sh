#!/bin/bash
curl -i -X GET http://localhost:8000/news?created__before=2020-09-01
