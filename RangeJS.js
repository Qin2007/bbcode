// RangeJS.js
function RangeJS(start, end, step = 1) {
    if (isNaN(start) || isNaN(end) || isNaN(step)) {
        throw NaN;
    }
    if (Math.trunc(step) === 0) {
        throw new Error('step is 0');
    }
    if (!new.target) {
        return Array.from((new RangeJS(this.start, this.end, this.step))[Symbol.iterator]());
    }
    this.start = Math.trunc(start);
    this.step = Math.trunc(step);
    this.reversed = this.step < 0;
    this.end = Math.trunc(end);
}

RangeJS.prototype[Symbol.iterator] = function* () {
    for (let i = this.start; i < this.end; i += this.step) {
        yield i;
    }
};
RangeJS.prototype[Symbol.toPrimitive] = function (hint) {
    if (hint === 'string') {
        return `Range(${this.start}, ${this.end}, ${this.step}),`;
    }
    let length = 0;
    for (const argument of this[Symbol.iterator]) {
        length++;
    }
    return length;
};
