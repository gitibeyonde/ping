#!/usr/bin/env python

import cv2
import boto3
import MySQLdb
import sys

s3 = boto3.resource('s3')
my_bucket = s3.Bucket('data.ibeyonde')
image_key = str(sys.argv[1])
user_name = str(sys.argv[2])

image_file_name = "/tmp/" + user_name + ".jpg"
file_key = "train_data/"+user_name+".xml"
file_name = "/tmp/"+file_key


db = MySQLdb.connect("34.210.169.216","admin","1b6y0nd6","ibe")
cursor = db.cursor()
sql = "select * from face_recog where user_name=%s"
row_count = cursor.execute(sql,user_name)
if (row_count>0):
	my_bucket.download_file(image_key,image_file_name)
        my_bucket.download_file(file_key,file_name)
        my_image = cv2.imread(image_file_name)
        gray = cv2.cvtColor(my_image,cv2.COLOR_BGR2GRAY)
        recognizer = cv2.face.createLBPHFaceRecognizer()
        recognizer.load(file_name)

        id,conf = recognizer.predict(gray)

        if conf<70.0:
                cursor.execute("select person_name from face_recog where user_name =%s AND  train_data =%s ",(user_name,id))
                
                person_name = cursor.fetchone()
                print person_name[0]

else:
	print ""

