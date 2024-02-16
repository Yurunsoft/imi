#!/bin/bash

__DIR__=$(cd `dirname $0`; pwd)
cd "$__DIR__/.."
__DIR__=$(pwd)

components=(
#  "core" 默认不处理
  "amqp"
  "apidoc"
  "fpm"
  "grpc"
  "jwt"
  "kafka"
  "mqtt"
  "pgsql"
  "queue"
  "rate-limit"
  "roadrunner"
  "rpc"
  "shared-memory"
  "smarty"
  "snowflake"
  "swoole"
  "swoole-tracker"
  "workerman"
  "workerman-gateway"
  "connection-center"
  "database"
  "model"
  "redis"
)

analyze_component() {
  component="$1"
  gen_baseline="$2"
  echo "Analyzing: $component, Generate Baseline: $gen_baseline"

  analyse_configuration=""

  args=()
  args+=("--memory-limit" "1G")

  if [ "$component" != "core" ]; then
    args+=("--configuration=phpstan-components.neon" "--autoload-file=src/Components/$component/vendor/autoload.php" "src/Components/$component")
  fi

  if [ "$gen_baseline" == "true" ]; then
    args+=("--generate-baseline=./phpstan-baseline/baseline-$component.neon" "--allow-empty-baseline")
  fi

  echo ./vendor/bin/phpstan analyse "${args[@]}"

  PHPSTAN_ANALYSE_COMPONENT_NAME="$component" PHPSTAN_GENERATE_BASELINE="$gen_baseline" ./vendor/bin/phpstan analyse "${args[@]}" -vvv
}

use_baseline="false"
input_components=()

while [[ $# -gt 0 ]]; do
  case "$1" in
    --baseline)
      use_baseline="true"
      shift
      ;;
    -b)
      use_baseline="true"
      shift
      ;;
    *)
      # 如果不是 --baseline 标志，将参数添加到 components 数组中
      input_components+=("$1")
      shift
      ;;
  esac
done

if [ ${#input_components[@]} -eq 0 ]; then
  # If no arguments are provided, analyze all components
  for component in "${components[@]}"; do
    analyze_component "$component" "$use_baseline"
  done
else
  # Analyze the specified components provided as arguments
  for component in "${input_components[@]}"; do
    if [[ " ${components[@]} " =~ " $component " || "core" == "$component" ]]; then
      analyze_component "$component" "$use_baseline"
    else
      echo "Invalid component name: $component"
    fi
  done
fi