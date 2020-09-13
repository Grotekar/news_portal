#!/bin/bash
curl -i -X GET http://localhost:8000/api/news?tag__in=[1,2]
