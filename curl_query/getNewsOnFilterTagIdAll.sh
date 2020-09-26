#!/bin/bash
curl -i -X GET http://localhost:8000/news?tag__all=[1,2]
