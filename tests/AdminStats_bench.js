import http from "k6/http";
import { sleep, check } from "k6";

export const options = {
  scenarios: {
    my_scenario: {
      executor: "constant-arrival-rate",
      duration: "1m", // Total duration of the test
      rate: 100, // Number of iterations per timeUnit
      timeUnit: "1s", // Define timeUnit
      preAllocatedVUs: 1, // How large the initial pool of VUs would be
      maxVUs: 10, // If the preAllocatedVUs are not enough, how much the pool can grow
      exec: "hit",
    },
  },
  thresholds: {
    http_req_duration: ["p(95)<500"], // 95 % of requests < 500 ms (adjust later)
  },
};

const BASE = __ENV.BASE_URL || "http://localhost:8080";
const PATH = "/api/public/foods"; // ← change endpoint if needed

export function hit() {
  const res = http.get(`${BASE}${PATH}`); //http get request Example: http:://localhost:8080/api/v1/vendors
  check(res, { "status 200": (r) => r.status === 200 });
  sleep(1);
}

// To run this script, use the command:
//cache
//curl http://localhost:8080/api/public/foods | Out-Null
// k6 run --env BASE_URL=http://localhost:8080 tests/AdminStats_bench.js
