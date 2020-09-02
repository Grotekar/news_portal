#!/bin/bash
curl -i -X GET http://localhost:8000/api/news?created__after=2020-09-01
