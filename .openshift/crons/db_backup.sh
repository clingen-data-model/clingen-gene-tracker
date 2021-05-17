#!/bin/bash

TODAY=`date +"%Y-%m-%d"`
NOW=`date +"%Y-%m-%d-%H:%M:%S"`

BACKUP_RETAIN_DAYS=30

mkdir -p ${BACKUP_PATH}/${TODAY}
echo "Backup started for database ${DATABASE_NAME}"

mysqldump -u root \
    --all-databases | gzip > ${BACKUP_PATH}/${TODAY}/${NOW}.sql.gz

if [ $? -eq 0 ]; then
  echo "db backup successful"
else
  echo "Error found during backup"
  exit 1
fi

REMOVE_DATE=`date +"%Y-%m-%d" --date="$BACKUP_RETAIN_DAYS days ago"`

if [ ! -z ${BACKUP_PATH} ];
then
    cd ${BACKUP_PATH}
    if [ ! -z ${REMOVE_DATE} ] && [ -d ${REMOVE_DATE} ];
    then
        echo "Remove ${REMOVE_DATE}"
        rm -rf ${REMOVE_DATE}
    else
    fi
else
fi