#!/usr/bin/env bash

NEXT_VERSION=421
PARTIAL_PREVIOUS_VERSION=
PARTIAL_CURRENT_VERSION=
PARTIAL_NEXT_VERSION=

OVERRIDE_CURRENT_FROM=411
OVERRIDE_CURRENT_TO=412
OVERRIDE_CURRENT_FROM_STR=4.1.1
OVERRIDE_CURRENT_TO_STR=4.1.2-p1

NEXT_FROM=$((NEXT_VERSION - 1))
NEXT_TO=$NEXT_VERSION

CURRENT_FROM=$((NEXT_VERSION - 2))
CURRENT_TO=$((NEXT_VERSION - 1))

if [ -z "${PARTIAL_CURRENT_VERSION}" ]; then
  NEXT_FROM_STR=${NEXT_FROM:0:1}.${NEXT_FROM:1:1}.${NEXT_FROM:2:2}
else
  CURRENT_FROM=$((CURRENT_FROM+2))
  NEXT_FROM=${NEXT_VERSION}${PARTIAL_CURRENT_VERSION}
  NEXT_FROM_STR=${NEXT_VERSION:0:1}.${NEXT_VERSION:1:1}.${NEXT_VERSION:2:2}${PARTIAL_CURRENT_VERSION}
fi

if [ -z "${PARTIAL_NEXT_VERSION}" ]; then
  NEXT_TO_STR=${NEXT_TO:0:1}.${NEXT_TO:1:1}.${NEXT_TO:2:2}
else
  NEXT_TO=${NEXT_VERSION}${PARTIAL_NEXT_VERSION}
  NEXT_TO_STR=${NEXT_VERSION:0:1}.${NEXT_VERSION:1:1}.${NEXT_VERSION:2:2}${PARTIAL_NEXT_VERSION}
fi

if [ -z "${PARTIAL_PREVIOUS_VERSION}" ]; then
  CURRENT_FROM_STR=${CURRENT_FROM:0:1}.${CURRENT_FROM:1:1}.${CURRENT_FROM:2:2}
else
  CURRENT_FROM=${NEXT_VERSION}${PARTIAL_PREVIOUS_VERSION}
  CURRENT_FROM_STR=${NEXT_VERSION:0:1}.${NEXT_VERSION:1:1}.${NEXT_VERSION:2:2}${PARTIAL_PREVIOUS_VERSION}
fi
if [ -z "${PARTIAL_CURRENT_VERSION}" ]; then
  CURRENT_TO_STR=${CURRENT_TO:0:1}.${CURRENT_TO:1:1}.${CURRENT_TO:2:2}
else
  CURRENT_TO=${NEXT_VERSION}${PARTIAL_CURRENT_VERSION}
  CURRENT_TO_STR=${NEXT_VERSION:0:1}.${NEXT_VERSION:1:1}.${NEXT_VERSION:2:2}${PARTIAL_CURRENT_VERSION}
fi

if [ -z "${OVERRIDE_CURRENT_FROM}" ]; then
  true
else
  CURRENT_FROM=${OVERRIDE_CURRENT_FROM}
fi

if [ -z "${OVERRIDE_CURRENT_TO}" ]; then
  true
else
  CURRENT_TO=${OVERRIDE_CURRENT_TO}
fi

if [ -z "${OVERRIDE_CURRENT_FROM_STR}" ]; then
  true
else
  CURRENT_FROM_STR=${OVERRIDE_CURRENT_FROM_STR}
fi

if [ -z "${OVERRIDE_CURRENT_TO_STR}" ]; then
  true
else
  CURRENT_TO_STR=${OVERRIDE_CURRENT_TO_STR}
fi

echo "NEXT_FROM_STR=${NEXT_FROM_STR}"
echo "NEXT_TO_STR=${NEXT_TO_STR}"
echo "CURRENT_FROM_STR=${CURRENT_FROM_STR}"
echo "CURRENT_TO_STR=${CURRENT_TO_STR}"
echo "NEXT_FROM=${NEXT_FROM}"
echo "NEXT_TO=${NEXT_TO}"
echo "CURRENT_FROM=${CURRENT_FROM}"
echo "CURRENT_TO=${CURRENT_TO}"
echo "NEXT_VERSION=${NEXT_VERSION}"
echo "PARTIAL_PREVIOUS_VERSION=${PARTIAL_PREVIOUS_VERSION}"
echo "PARTIAL_CURRENT_VERSION=${PARTIAL_CURRENT_VERSION}"
echo "PARTIAL_NEXT_VERSION=${PARTIAL_NEXT_VERSION}"

files=`find . -type f -name '*.php' -or -name '*.twig' -or -name 'services.yaml'`

for file in $files; do
  # MACOS専用コマンド
  if [ "$(uname)" == "Darwin" ]; then
    sed -i '' "s/EccubeUpdater${CURRENT_FROM}to${CURRENT_TO}/EccubeUpdater${NEXT_FROM}to${NEXT_TO}/g" $file
    sed -i '' "s/eccube_updater${CURRENT_FROM}to${CURRENT_TO}/eccube_updater${NEXT_FROM}to${NEXT_TO}/g" $file
    sed -i '' "s/eccube_updater_${CURRENT_FROM}_to_${CURRENT_TO}/eccube_updater_${NEXT_FROM}_to_${NEXT_TO}/g" $file
    sed -i '' "s/${CURRENT_TO_STR}/${NEXT_TO_STR}/g" $file
    sed -i '' "s/${CURRENT_FROM_STR}/${NEXT_FROM_STR}/g" $file
    sed -i '' "s/:update${CURRENT_FROM}to${CURRENT_TO}/:update${NEXT_FROM}to${NEXT_TO}/g" $file
    sed -i '' "s/eccube_update_plugin_${CURRENT_FROM}_${CURRENT_TO}_php_path/eccube_update_plugin_${NEXT_FROM}_${NEXT_TO}_php_path/g" $file
  # LINUX専用コマンド
  elif [ "$(expr substr $(uname -s) 1 5)" == "Linux" ]; then
    sed -i "s/EccubeUpdater${CURRENT_FROM}to${CURRENT_TO}/EccubeUpdater${NEXT_FROM}to${NEXT_TO}/g" $file
    sed -i "s/eccube_updater${CURRENT_FROM}to${CURRENT_TO}/eccube_updater${NEXT_FROM}to${NEXT_TO}/g" $file
    sed -i "s/eccube_updater_${CURRENT_FROM}_to_${CURRENT_TO}/eccube_updater_${NEXT_FROM}_to_${NEXT_TO}/g" $file
    sed -i "s/${CURRENT_TO_STR}/${NEXT_TO_STR}/g" $file
    sed -i "s/${CURRENT_FROM_STR}/${NEXT_FROM_STR}/g" $file
    sed -i "s/:update${CURRENT_FROM}to${CURRENT_TO}/:update${NEXT_FROM}to${NEXT_TO}/g" $file
    sed -i "s/eccube_update_plugin_${CURRENT_FROM}_${CURRENT_TO}_php_path/eccube_update_plugin_${NEXT_FROM}_${NEXT_TO}_php_path/g" $file
  fi
done

# MACOS専用コマンド
if [ "$(uname)" == "Darwin" ]; then
  sed -i '' "s/EccubeUpdater${CURRENT_FROM}to${CURRENT_TO}/EccubeUpdater${NEXT_FROM}to${NEXT_TO}/g" composer.json
  sed -i '' "s/eccubeupdater${CURRENT_FROM}to${CURRENT_TO}/eccubeupdater${NEXT_FROM}to${NEXT_TO}/g" composer.json
# LINUX専用コマンド
elif [ "$(expr substr $(uname -s) 1 5)" == "Linux" ]; then
  sed -i "s/EccubeUpdater${CURRENT_FROM}to${CURRENT_TO}/EccubeUpdater${NEXT_FROM}to${NEXT_TO}/g" composer.json
  sed -i "s/eccubeupdater${CURRENT_FROM}to${CURRENT_TO}/eccubeupdater${NEXT_FROM}to${NEXT_TO}/g" composer.json
fi

