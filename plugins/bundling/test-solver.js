const map = `? ? ? ? ? ?
2 2 2 1 2 2
2 x 2 0 1 x
2 x 2 1 2 2
1 1 1 1 x 1
0 0 0 1 1 1`.split('\n').map(row => row.split(' '));
const restCountMines = 2;
const unsolved = map.reduce((acc, row, index) => ([
	...acc, ...[
		...row.reduce((innerAcc, value, innerIndex) => [
			...innerAcc, (value === '?' ? [index, innerIndex] : [])
		], [])
	]
]), []).filter(map => !!map.length);

function deepClone(value) {
	if (typeof value !== 'object') {
		return value;
	}

	const clone = value instanceof Array ? [] : {};

	for (let index in value) {
		if (value.hasOwnProperty(index)) {
			clone[index] = deepClone(value[index]);
		}
	}

	return clone;
}

function printPermutations() {
	const permutations = [];
	const levels = [];

	const isLevelAtIndexExists = (level, index) => {
		for (var j = 0; j < level; j++) {
			if (levels[j] === index) {
				return true;
			}
		}
		return false;
	};

	const isPermutationExists = (permutation) => {
		return permutations.some((iterablePermutation) => iterablePermutation.every(index => permutation.includes(index)));
	};

	const next = level => {
		if (level === restCountMines) {
			const permutation = [];

			for (let i = 0; i < restCountMines; i++) {
				permutation.push(levels[i]);
			}

			if (!isPermutationExists(permutation)) {
				permutations.push(permutation);
			}
		}
		else {
			unsolved.forEach(([row, col], index) => {
				if (isLevelAtIndexExists(level, index)) {
					return;
				}

				levels[level] = index;
				next(level + 1);
			});
		}
	};

	next(0);

	return permutations.map((permutation) => {
		const clone = deepClone(map);

		permutation.forEach(index => {
			const [row, col] = unsolved[index];

			clone[row][col] = 'x';
		});

		return clone;
	});
}

console.log(restCountMines, unsolved.length);
const m = printPermutations();
console.log(m, m.length);
