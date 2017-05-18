# Slim group performance test

## Setup

1. After cloning the repo, run `composer install`
2. Add the nginx.conf file to your nginx config
3. Compare the performance of the following routes:
    1. http://localhost:8080/all/group1/sub1/route1
    2. http://localhost:8080/single/group1/sub1/route1

Ideally the performance would be about the same for both routes,
but currently (Slim 3.8.1) the first route is many times slower.

The reason for this is that Slim currently runs all the code to
generate routes inside group and subgroup callbacks, even if the
group/subgroup does not contain the route being navigated to.
