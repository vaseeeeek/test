class MineSweeper {
	static eachComparator() {
		return true;
	}

	static getIncludeComparator(...includes) {
		return (value) => includes.includes(value);
	}

	static getExcludeComparator(...excludes) {
		return (value) => !excludes.includes(value);
	}

	static deepClone(value) {
		if (typeof value !== 'object') {
			return value;
		}

		const clone = value instanceof Array ? [] : {};

		for (let index in value) {
			if (value.hasOwnProperty(index)) {
				clone[index] = MineSweeper.deepClone(value[index]);
			}
		}

		return clone;
	}

	static createPermutations(values, count) {
		const permutations = [];
		const levels = [];

		const isLevelAtIndexExists = (level, index) => {
			for (let j = 0; j < level; j++) {
				if (levels[j] === index) {
					return true;
				}
			}

			return false;
		};

		const isPermutationExists = (permutation) => {
			return permutations.some(
				(iterablePermutation) => iterablePermutation.every(index => permutation.includes(index)));
		};

		const next = level => {
			if (level === count) {
				const permutation = [];

				for (let i = 0; i < count; i++) {
					permutation.push(levels[i]);
				}

				if (!isPermutationExists(permutation)) {
					permutations.push(permutation);
				}
			}
			else {
				values.forEach((value, index) => {
					if (isLevelAtIndexExists(level, index)) {
						return;
					}

					levels[level] = index;
					next(level + 1);
				});
			}
		};

		next(0);

		return permutations.map((permutation) => permutation.map(index => values[index]));
	}

	static find([map], comparator) {
		return map.reduce((acc, row, index) => ([
			...acc, ...[
				...row.reduce((innerAcc, value, innerIndex) => [
					...innerAcc, (comparator(value) ? [index, innerIndex] : [])
				], [])
			]
		]), []).filter(map => !!map.length);
	}

	static getNeighbors([map, rowsCount, colsCount], row, col, comparator) {
		if (!MineSweeper.bufferNeighbors) {
			MineSweeper.bufferNeighbors = new Map();
		}

		if (!MineSweeper.bufferNeighbors.get(map)) {
			MineSweeper.bufferNeighbors.set(map, {});
		}

		const buffer = MineSweeper.bufferNeighbors.get(map);

		if (!buffer[row]) {
			buffer[row] = {};
		}

		if (!buffer[row][col]) {
			buffer[row][col] = [
				[row - 1, col - 1],
				[row - 1, col],
				[row - 1, col + 1],
				[row, col - 1],
				[row, col + 1],
				[row + 1, col - 1],
				[row + 1, col],
				[row + 1, col + 1],
			].filter(([iterableRow, iterableCol]) => iterableRow >= 0 && iterableRow < rowsCount && iterableCol >= 0
				&& iterableCol < colsCount);
		}

		if (!comparator) {
			comparator = MineSweeper.eachComparator;
		}

		return buffer[row][col].filter(([iterableRow, iterableCol]) => comparator(map[iterableRow][iterableCol]));
	}

	static isValidMap([map, rowsCount, colsCount]) {
		return MineSweeper.find([map], MineSweeper.getExcludeComparator('?', 'x')).every(
			([row, col]) => MineSweeper.getNeighbors([map, rowsCount, colsCount], row, col,
				MineSweeper.getIncludeComparator('x')).length === +map[row][col]);
	}

	constructor(map, countMines) {
		this.deserialize(map);
		this.countMines = +countMines;

		this.predicateWithSameUnsolvedNeighborsCount = this.predicateWithSameUnsolvedNeighborsCount.bind(this);
		this.predicateWithNotAssertedUnsolvedNeighbors = this.predicateWithNotAssertedUnsolvedNeighbors.bind(this);
		this.predicateWithUnsolvedAndSameMinedNeighborsCount
			= this.predicateWithUnsolvedAndSameMinedNeighborsCount.bind(this);
		this.predicateWithUnsolvedRestNeighborsCount = this.predicateWithUnsolvedRestNeighborsCount.bind(this);
	}

	getCurrentCountMines() {
		return this.find(MineSweeper.getIncludeComparator('x')).length;
	}

	safeOpen(row, col) {
		if (this.map[row][col] !== '?') {
			return;
		}

		this.map[row][col] = open(row, col);
	}

	markAsMine(row, col) {
		this.map[row][col] = 'x';
	}

	solve() {
		let iterations = 0;

		while (this.getCurrentCountMines() !== this.countMines) {
			iterations++;

			const iterationMap = JSON.stringify(this.map);

			this.solveObvious();

			if (iterationMap === JSON.stringify(this.map)) {
				break;
			}
		}

		this.getUnsolvedNotAsserted().forEach(value => this.safeOpen(...value));

		if (this.getCurrentCountMines() === this.countMines) {
			// Finalization. Open rest unsolved

			this.find(MineSweeper.getIncludeComparator('?')).forEach(value => this.safeOpen(...value));
		}
		else {
			this.solveComplicated();
		}

		// console.log(this.serialize());
	}

	solveComplicated() {
		// Step 1. Create all permutations

		const unsolved = this.find(MineSweeper.getIncludeComparator('?'));
		const restCountMines = this.countMines - this.getCurrentCountMines();

		const permutations = MineSweeper.createPermutations(unsolved, restCountMines).map((permutation) => {
			const clone = MineSweeper.deepClone(this.map);

			permutation.forEach(([row, col]) => {
				clone[row][col] = 'x';
			});

			return clone;
		});

		// Step 2. Validate permutations

		const validPermutations = permutations.filter(
			permutation => MineSweeper.isValidMap([permutation, this.rowsCount, this.colsCount]));

		if (validPermutations.length > 1) {
			console.log(
				validPermutations.map(map => map.reduce((acc, row) => [...acc, row.join(' ')], '').join('\n')).join(
					'\n\n'));

			throw new Error('Ambiguity');
		}
		else if (validPermutations.length === 1) {
			const [validPermutation] = validPermutations;

			this.map = validPermutation;
			this.find(MineSweeper.getIncludeComparator('?')).forEach(value => this.safeOpen(...value));
		}
		else {
			throw new Error('Idk');
		}
	}

	solveObvious() {
		// Level 1. Open zeros with unsolved neighbors
		// while (this.getZerosWithUnsolvedNeighbors().length) {
		this.getZerosWithUnsolvedNeighbors().forEach(([row, col]) => {
			this.getNeighbors(row, col).forEach(value => this.safeOpen(...value));
		});
		// }

		let solved = this.getSolved();

		// Level 2. Mark as mine all solved with same unsolved neighbors count
		// while (this.getSolvedWithSameUnsolvedNeighborsCount().length) {
		solved.filter(this.predicateWithSameUnsolvedNeighborsCount).forEach(([row, col]) => {
			this.getNeighbors(row, col, MineSweeper.getIncludeComparator('?')).forEach(
				value => this.markAsMine(...value));
		});
		//}

		// Level 3. Open unsolved where all neighbors with same count marked as mines
		// while (this.getSolvedWithUnsolvedAndSameMinedNeighborsCount().length) {
		solved.filter(this.predicateWithUnsolvedAndSameMinedNeighborsCount).forEach(([row, col]) => {
			this.getNeighbors(row, col, MineSweeper.getIncludeComparator('?')).forEach(
				value => this.safeOpen(...value));
		});
		// }

		solved = this.getSolved();

		// Level 4. Mark as mine with unsolved rest neighbors count
		// while (this.getSolvedWithUnsolvedRestNeighborsCount().length) {
		solved.filter(this.predicateWithUnsolvedRestNeighborsCount).forEach(([row, col]) => {
			this.getNeighbors(row, col, MineSweeper.getIncludeComparator('?')).forEach(
				value => this.markAsMine(...value));
		});
		// }

		// Level 5. Mark asserts
		// while (this.getSolvedWithNotAssertedUnsolvedNeighbors().length) {
		solved.filter(this.predicateWithNotAssertedUnsolvedNeighbors).forEach(([row, col]) => {
			this.getNeighbors(row, col, MineSweeper.getIncludeComparator('?')).forEach(([row, col]) => {
				this.asserts[row][col] = true;
			});
		});
		// }
	}

	getZerosWithUnsolvedNeighbors() {
		return this.find(MineSweeper.getIncludeComparator('0')).filter(
			([row, col]) => this.getNeighbors(row, col).some(([row, col]) => this.map[row][col] === '?'));
	}

	getSolved() {
		return this.find(MineSweeper.getExcludeComparator('?', 'x'));
	}

	predicateWithSameUnsolvedNeighborsCount([row, col]) {
		const unsolvedOrMined = this.getNeighbors(row, col, MineSweeper.getIncludeComparator('?', 'x'));
		const mined = this.getNeighbors(row, col, MineSweeper.getIncludeComparator('x'));

		return unsolvedOrMined.length === +this.map[row][col] && mined.length !== +this.map[row][col];
	}

	predicateWithUnsolvedAndSameMinedNeighborsCount([row, col]) {
		const unsolved = this.getNeighbors(row, col, MineSweeper.getIncludeComparator('?'));
		const mined = this.getNeighbors(row, col, MineSweeper.getIncludeComparator('x'));

		return mined.length === +this.map[row][col] && unsolved.length;
	}

	predicateWithUnsolvedRestNeighborsCount([row, col]) {
		const unsolved = this.getNeighbors(row, col, MineSweeper.getIncludeComparator('?'));
		const mined = this.getNeighbors(row, col, MineSweeper.getIncludeComparator('x'));

		return unsolved.length && unsolved.length === (+this.map[row][col] - mined.length);
	}

	predicateWithNotAssertedUnsolvedNeighbors([row, col]) {
		return this.getNeighbors(row, col, MineSweeper.getIncludeComparator('?')).some(
			([row, col]) => !this.asserts[row][col]);
	}

	getUnsolvedNotAsserted() {
		return this.find(MineSweeper.getIncludeComparator('?'))
			.filter(([row, col]) => !this.asserts[row][col]);
	}

	find(comparator) {
		return MineSweeper.find([this.map], comparator);
	}

	getNeighbors(row, col, comparator) {
		return MineSweeper.getNeighbors([this.map, this.rowsCount, this.colsCount], row, col, comparator);
	}

	deserialize(map) {
		const rowsString = map.split(/\n/);

		this.map = rowsString.map(row => row.split(' '));
		this.rowsCount = rowsString.length;
		this.colsCount = this.map[0] ? this.map[0].length : 0;
		this.asserts = this.map.reduce((acc, row) => [...acc, row.map(() => false)], []);
	}

	serialize() {
		return this.map.reduce((acc, row) => [...acc, row.join(' ')], '').join('\n');
	}
}

function solveMine(map, countMines) {
	try {
		const mineSweeper = new MineSweeper(map, countMines);
		mineSweeper.solve();

		console.log(map);

		return mineSweeper.serialize();
	}
	catch (e) {
		console.log(e);
		return '?';
	}
}
