#!/bin/bash
eval "$(ssh-agent -s)"
echo "$GIT_DEPLOY_KEY" > deploy_key.pem
chmod 600 deploy_key.pem
ssh-add deploy_key.pem
git remote add deploy $GIT_DEPLOY_URL
git push deploy master