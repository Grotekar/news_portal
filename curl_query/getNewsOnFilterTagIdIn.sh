#!/bin/bash
curl -i -X GET http://localhost:8000/news?tag__in=[1,2]
