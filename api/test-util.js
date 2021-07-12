function assert(actual, expected) {
  console.assert(
    JSON.stringify(actual) === JSON.stringify(expected),
    JSON.stringify({ actual, expected }, null, 2));
}

module.exports = { assert };

