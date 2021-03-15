# create as many databases as you want
CREATE DATABASE IF NOT EXISTS gene_tracker;
CREATE DATABASE IF NOT EXISTS gene_tracker_test;
CREATE DATABASE IF NOT EXISTS testing;

# grant rights to user `user`
GRANT ALL ON *.* TO 'gene_tracker'@'%';
GRANT ALL ON gene_tracker_test.* TO 'gene_tracker'@'%';
GRANT ALL ON testing.* TO 'gene_tracker'@'%';
