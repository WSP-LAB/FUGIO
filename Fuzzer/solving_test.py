from z3 import *

ConstSolver = Solver()
x = Bool('x')

ConstSolver.add(x)

print(ConstSolver.check())

'''
# SMT Solve
ConstModel = ConstSolver.model()
for model_dec in ConstModel.decls():
    print(model_dec.name(), ConstModel[model_dec])
'''
